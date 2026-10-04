<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Exceptions\OrderResolutionException;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin resolution for orders the supplier left unsettled (PROCESSING past the
 * polling limit, or PARTIAL_FAILED). Funds are still locked on these orders.
 */
class OrderResolutionService
{
    /**
     * Give the reservation back to the customer and close the order as FAILED.
     *
     * @throws OrderResolutionException
     */
    public function release(Order $order, User $admin, string $reason = ''): Order
    {
        $this->assertResolvable($order);

        DB::transaction(function () use ($order) {
            $order->releaseFunds();
            $order->update(['status' => OrderStatusEnum::FAILED, 'completed_at' => now()]);
        });

        $this->log('released', $order, $admin, $reason);

        return $order->refresh();
    }

    /**
     * Charge the full sale amount and close the order as SUCCEEDED.
     *
     * @throws OrderResolutionException
     */
    public function charge(Order $order, User $admin, string $reason = ''): Order
    {
        $this->assertResolvable($order);

        DB::transaction(function () use ($order) {
            $order->chargeFunds();
            $order->update(['status' => OrderStatusEnum::SUCCEEDED, 'paid_at' => now(), 'completed_at' => now()]);
        });

        $this->log('charged', $order, $admin, $reason);

        return $order->refresh();
    }

    public static function isResolvable(Order $order): bool
    {
        return in_array($order->status, [OrderStatusEnum::PROCESSING, OrderStatusEnum::PARTIAL_FAILED], true);
    }

    /**
     * @throws OrderResolutionException
     */
    private function assertResolvable(Order $order): void
    {
        if (! self::isResolvable($order)) {
            throw new OrderResolutionException("Order #{$order->id} is {$order->status->name()} and cannot be resolved manually.");
        }
    }

    private function log(string $action, Order $order, User $admin, string $reason): void
    {
        Log::channel('bamboo')->notice("Order manually $action by admin", [
            'order_id' => $order->id,
            'admin_id' => $admin->id,
            'amount' => $order->sale_amount,
            'reason' => $reason,
        ]);
    }
}
