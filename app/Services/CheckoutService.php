<?php

namespace App\Services;

use App\Enums\OrderStatusEnum;
use App\Exceptions\CheckoutException;
use App\Jobs\PlaceSupplierOrder;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use vahidkaargar\LaravelWallet\Exceptions\InsufficientFundsException;
use vahidkaargar\LaravelWallet\Exceptions\WalletNotFoundException;

class CheckoutService
{
    public function __construct(private readonly PricingService $pricing) {}

    /**
     * Turn the user's cart into an order and reserve its price in the wallet.
     *
     * Money flow: the sale amount is locked now; PlaceSupplierOrder/SyncSupplierOrder
     * withdraw it when the supplier delivers, or unlock it when the supplier fails.
     *
     * @throws CheckoutException
     */
    public function checkout(User $user, string $walletSlug): Order
    {
        if (! $user->is_active) {
            throw new CheckoutException('Your account is inactive.');
        }

        if (! $user->can_place_order) {
            throw new CheckoutException('Your account is not allowed to place orders.');
        }

        $lock = Cache::lock("checkout:{$user->id}", 30);
        if (! $lock->get()) {
            throw new CheckoutException('A checkout is already in progress.');
        }

        try {
            $order = $this->createOrder($user, $walletSlug);
        } finally {
            $lock->release();
        }

        PlaceSupplierOrder::dispatch($order->id)->afterCommit();

        return $order;
    }

    /**
     * @throws CheckoutException
     */
    private function createOrder(User $user, string $walletSlug): Order
    {
        $cart = new CartService($user);
        $lines = $this->priceLines($user, $cart->items());

        try {
            $wallet = $user->getWallet($walletSlug);
        } catch (WalletNotFoundException) {
            throw new CheckoutException('Choose one of your wallets.');
        }

        if (! $wallet->is_active || strtoupper($wallet->currency) !== 'USD') {
            throw new CheckoutException('Orders can only be paid from an active USD wallet.');
        }

        $saleTotal = round(array_sum(array_column($lines, 'sale_amount')), 2);
        $costTotal = round(array_sum(array_column($lines, 'purchase_amount')), 2);

        $order = DB::transaction(function () use ($user, $wallet, $walletSlug, $lines, $saleTotal, $costTotal) {
            $order = Order::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'status' => OrderStatusEnum::CREATED,
                'purchase_amount' => $costTotal,
                'sale_amount' => $saleTotal,
            ]);
            $order->items()->createMany($lines);

            try {
                $user->lockFunds($walletSlug, $saleTotal, $order->walletReference(), meta: ['order_id' => $order->id]);
            } catch (InsufficientFundsException) {
                throw new CheckoutException('Insufficient wallet balance for this order.');
            }

            return $order;
        });

        $cart->empty();

        return $order;
    }

    /**
     * @param  array<string, array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     *
     * @throws CheckoutException
     */
    private function priceLines(User $user, array $items): array
    {
        if ($items === []) {
            throw new CheckoutException('Your cart is empty.');
        }

        $lines = [];
        foreach ($items as $item) {
            $faceValue = $item['purchase'];
            if ((float) $faceValue !== (float) (int) $faceValue) {
                throw new CheckoutException('Card amounts must be whole numbers.');
            }

            $quote = $this->pricing->quote($user, $item['supplier'], $item['product_id'], $faceValue, (int) $item['quantity']);

            $lines[] = [
                'name' => $quote['product']['name'],
                'supplier' => $item['supplier'],
                'relation' => [
                    'product_id' => (int) $item['product_id'],
                    'face_value' => (int) $faceValue,
                    'face_currency' => $quote['product']['purchase']['currency'] ?? null,
                ],
                'quantity' => (int) $item['quantity'],
                'purchase_amount' => round($quote['cost'], 2),
                'profit_percentage' => $quote['fee_percentage'],
                'sale_amount' => round($quote['sale'], 2),
            ];
        }

        return $lines;
    }
}
