<?php

namespace App\Jobs;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Sends a CREATED order to the supplier, then hands over to SyncSupplierOrder.
 *
 * Runs once: the request id makes a resend idempotent on the supplier side, but a
 * failed attempt is resolved by polling, not by retrying the purchase.
 */
class PlaceSupplierOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::query()->with('items')->find($this->orderId);
        if ($order === null || $order->status !== OrderStatusEnum::CREATED) {
            return;
        }

        $accountId = (int) config('services.bamboo.account_id');
        if ($accountId <= 0) {
            throw new RuntimeException('services.bamboo.account_id (BAMBOO_ACCOUNT_ID) is not configured.');
        }

        // From here on the supplier may have received the order, so funds stay locked until polling resolves it.
        $order->update(['status' => OrderStatusEnum::PROCESSING]);

        try {
            $response = bamboo()->orders()
                ->setRequestId($order->supplierRequestId())
                ->setAccountId($accountId)
                ->setProducts($order->items->map(fn (OrderItem $item) => [
                    'ProductId' => (int) $item->relation['product_id'],
                    'Quantity' => (int) $item->quantity,
                    'Value' => (int) $item->relation['face_value'],
                ])->all())
                ->checkout()
                ->toArray();

            Log::channel('bamboo')->info('Order checkout sent', [
                'order_id' => $order->id,
                'success' => $response['success'] ?? null,
                'message' => $response['message'] ?? null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        SyncSupplierOrder::dispatch($order->id)->delay(now()->addSeconds(SyncSupplierOrder::POLL_SECONDS));
    }

    /**
     * Nothing reached the supplier while the order is still CREATED, so the reservation can be released.
     */
    public function failed(?Throwable $exception): void
    {
        $order = Order::query()->with(['user', 'wallet'])->find($this->orderId);
        if ($order === null || $order->status !== OrderStatusEnum::CREATED) {
            return;
        }

        $order->releaseFunds();
        $order->update(['status' => OrderStatusEnum::FAILED, 'completed_at' => now()]);
    }
}
