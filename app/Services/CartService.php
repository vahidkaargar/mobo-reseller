<?php

namespace App\Services;


use App\Enums\SuppliersEnum;
use App\Facades\FeeCalculator;
use App\Models\Cart;
use App\Models\User;
use Throwable;

class CartService
{
    /**
     * @var Cart
     */
    private Cart $cart;

    /**
     * @param User|null $user
     */
    public function __construct(private ?User $user)
    {
        $this->cart = $this->get();
        return $this;
    }

    /**
     * @return array
     * @throws Throwable
     */
    public function items(): array
    {
        $items = $this->cart->refresh()->items ?? [];
        return $this->prepareProducts($items);
    }

    /**
     * @param string $supplier
     * @param int|string $productId
     * @param int|float $purchase
     * @param int $quantity
     * @return Cart
     * @throws Throwable
     */
    public function add(string $supplier, int|string $productId, int|float $purchase, int $quantity): Cart
    {
        $items = $this->items();

        $key = md5(base64_encode($supplier . $productId . $purchase));
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
     * @param string $key
     * @return Cart
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

    /**
     * @return Cart
     */
    public function empty(): Cart
    {
        return $this->save([]);
    }

    /**
     * @return Cart
     */
    private function get(): Cart
    {
        return $this->user->cart()->firstOrNew();
    }

    /**
     * @param array $items
     * @return Cart
     */
    private function save(array $items): Cart
    {
        $this->cart->items = $items;
        $this->cart->save();
        return $this->cart;
    }

    /**
     * @param $items
     * @return array
     * @throws Throwable
     */
    private function prepareProducts($items): array
    {
        foreach ($items as &$item) {
            $product = SuppliersEnum::tryFrom($item['supplier'])->catalog()->product($item['product_id']);
            $sale = $product['sale']['fixed'] ? $product['sale']['unit'] : $product['sale']['unit'] * $item['purchase'];
            $sale = $sale * $item['quantity'];
            $amount = FeeCalculator::supplier($item['supplier'])->product($item['product_id'])->addFeeTo($sale);
            $item['amount'] = exchange($amount, $product['sale']['currency']);
            unset($product['sale'], $product['discount']);
            $item['product'] = $product;
        }
        return $items;
    }
}
