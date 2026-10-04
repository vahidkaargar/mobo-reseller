<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use Throwable;

class CartService
{
    private Cart $cart;

    public function __construct(private ?User $user)
    {
        $this->cart = $this->get();

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function items(): array
    {
        $items = $this->cart->refresh()->items ?? [];

        return $this->prepareProducts($items);
    }

    /**
     * @throws Throwable
     */
    public function add(string $supplier, int|string $productId, int|float $purchase, int $quantity): Cart
    {
        $items = $this->items();

        $key = md5(base64_encode($supplier.$productId.$purchase));
        $items[$key] = [
            'supplier' => $supplier,
            'product_id' => $productId,
            'purchase' => $purchase,
            'amount' => 0,
            'quantity' => max(1, round($quantity)),
        ];

        return $this->save($items);
    }

    /**
     * @throws Throwable
     */
    public function remove(string $key): Cart
    {
        $items = $this->items();

        if (isset($items[$key])) {
            unset($items[$key]);
        }

        return $this->save($items);
    }

    public function empty(): Cart
    {
        return $this->save([]);
    }

    private function get(): Cart
    {
        return $this->user->cart()->firstOrNew();
    }

    private function save(array $items): Cart
    {
        $this->cart->items = $items;
        $this->cart->save();

        return $this->cart;
    }

    /**
     * @throws Throwable
     */
    private function prepareProducts($items): array
    {
        foreach ($items as &$item) {
            $quote = app(PricingService::class)->quote($this->user, $item['supplier'], $item['product_id'], $item['purchase'], $item['quantity']);
            $item['amount'] = $quote['sale'];
            $product = $quote['product'];
            unset($product['sale'], $product['discount']);
            $item['product'] = $product;
        }

        return $items;
    }
}
