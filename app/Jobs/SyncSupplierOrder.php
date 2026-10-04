<?php

namespace App\Jobs;

use App\Enums\BambooOrderStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Polls the supplier for a PROCESSING order and settles the wallet.
 *
 * Settles only on an explicit supplier result:
 *  - Succeeded with cards for every line: store cards, unlock + withdraw, SUCCEEDED.
 *  - Failed: unlock, FAILED.
 *  - PartialFailed, or Succeeded with lines missing cards: store what arrived, keep funds locked,
 *    PARTIAL_FAILED for an admin to resolve.
 * Anything else is polled again; after MAX_ATTEMPTS the order stays PROCESSING with funds locked.
 */
class SyncSupplierOrder implements ShouldQueue
{
    use Queueable;

    public const POLL_SECONDS = 30;

    public const MAX_ATTEMPTS = 40;

    public int $tries = 1;

    public function __construct(public int $orderId, public int $attempt = 1) {}

    public function handle(): void
    {
        $order = Order::query()->with(['items', 'user', 'wallet'])->find($this->orderId);
        if ($order === null || $order->status !== OrderStatusEnum::PROCESSING) {
            return;
        }

        try {
            $response = bamboo()->orders()->get($order->supplierRequestId())->toArray();
        } catch (Throwable $e) {
            report($e);
            $response = [];
        }

        $body = ($response['success'] ?? false) ? ($response['body'] ?? []) : [];
        $status = BambooOrderStatusEnum::tryFrom((string) ($body['status'] ?? ''));

        match ($status) {
            BambooOrderStatusEnum::SUCCEEDED, BambooOrderStatusEnum::PARTIAL_FAILED => $this->deliver($order, $body, $status),
            BambooOrderStatusEnum::FAILED => $this->release($order),
            default => $this->pollAgain($order, $status),
        };
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function deliver(Order $order, array $body, BambooOrderStatusEnum $status): void
    {
        $cardsByProduct = collect($body['items'] ?? [])
            ->groupBy(fn (array $item) => (int) ($item['productId'] ?? 0))
            ->map(fn ($items) => $items->flatMap(fn (array $item) => $item['cards'] ?? [])->values()->all());

        $complete = $status === BambooOrderStatusEnum::SUCCEEDED;

        DB::transaction(function () use ($order, $cardsByProduct, &$complete) {
            foreach ($order->items as $item) {
                /** @var OrderItem $item */
                $cards = $cardsByProduct->get((int) $item->relation['product_id'], []);
                $item->update(['cards' => $cards]);
                $complete = $complete && count($cards) >= $item->quantity;
            }

            if (! $complete) {
                $order->update(['status' => OrderStatusEnum::PARTIAL_FAILED]);

                return;
            }

            $order->chargeFunds();
            $order->update(['status' => OrderStatusEnum::SUCCEEDED, 'paid_at' => now(), 'completed_at' => now()]);
        });

        if (! $complete) {
            Log::channel('bamboo')->warning('Order needs admin review: incomplete delivery, funds still locked', [
                'order_id' => $order->id,
                'supplier_status' => $status->value,
            ]);
        }
    }

    private function release(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->releaseFunds();
            $order->update(['status' => OrderStatusEnum::FAILED, 'completed_at' => now()]);
        });
    }

    private function pollAgain(Order $order, ?BambooOrderStatusEnum $status): void
    {
        if ($this->attempt >= self::MAX_ATTEMPTS) {
            Log::channel('bamboo')->warning('Order needs admin review: no final supplier status, funds still locked', [
                'order_id' => $order->id,
                'supplier_status' => $status?->value,
                'attempts' => $this->attempt,
            ]);

            return;
        }

        self::dispatch($order->id, $this->attempt + 1)->delay(now()->addSeconds(self::POLL_SECONDS));
    }
}
