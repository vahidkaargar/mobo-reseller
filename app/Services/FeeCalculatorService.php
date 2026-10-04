<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Number;
use RuntimeException;
use Throwable;

class FeeCalculatorService
{
    /**
     * Per-product fee overrides for the current supplier, keyed by supplier product id.
     *
     * @var array<string, string|float>|null
     */
    private ?array $fees = null;

    private mixed $product = null;

    private ?string $supplier = null;

    public function __construct(private readonly ?User $user) {}

    /**
     * @throws Throwable
     */
    public function addFeeTo(int|float $amount): float
    {
        return add_percent($amount, $this->fee());
    }

    /**
     * The fee percentage for the selected supplier product: the user's per-product
     * override when one exists, otherwise the user's default fee.
     *
     * @throws Throwable
     */
    public function fee(): float
    {
        throw_if(empty($this->supplier) || empty($this->product), 'supplier and product methods must be called');

        $user = $this->user();

        if ($this->fees === null) {
            $this->fees = $user->fees()
                ->where('supplier_name', $this->supplier)
                ->pluck('fee_percentage', 'supplier_id')
                ->toArray();
        }

        return Number::parseFloat((string) ($this->fees[$this->product] ?? $user->fee_percentage));
    }

    public function supplier(string $supplier): static
    {
        if ($supplier !== $this->supplier) {
            $this->fees = null;
        }
        $this->supplier = $supplier;

        return $this;
    }

    public function product(int|string $product): static
    {
        $this->product = $product;

        return $this;
    }

    private function user(): User
    {
        return $this->user ?? throw new RuntimeException('FeeCalculatorService needs an authenticated user.');
    }
}
