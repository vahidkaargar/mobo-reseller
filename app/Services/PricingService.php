<?php

namespace App\Services;

use App\Enums\SuppliersEnum;
use App\Models\User;
use InvalidArgumentException;

class PricingService
{
    /**
     * Price one cart line for a user.
     *
     * cost is what the supplier charges us, sale is what the user pays (cost plus the user's fee).
     * Both are converted to USD with exchange().
     *
     * @return array{product: array<string, mixed>, fee_percentage: float, cost: float, sale: float, cost_currency: string}
     */
    public function quote(User $user, string $supplier, int|string $productId, int|float $faceValue, int $quantity): array
    {
        $supplierEnum = SuppliersEnum::tryFrom($supplier)
            ?? throw new InvalidArgumentException("Unknown supplier: $supplier");

        $product = $supplierEnum->catalog()->product($productId);

        $unitCost = $product['sale']['fixed'] ? $product['sale']['unit'] : $product['sale']['unit'] * $faceValue;
        $cost = $unitCost * $quantity;

        $fees = (new FeeCalculatorService($user))->supplier($supplier)->product($productId);
        $feePercentage = $fees->fee();

        return [
            'product' => $product,
            'fee_percentage' => $feePercentage,
            'cost' => exchange($cost, $product['sale']['currency']),
            'sale' => exchange(add_percent($cost, $feePercentage), $product['sale']['currency']),
            'cost_currency' => $product['sale']['currency'],
        ];
    }
}
