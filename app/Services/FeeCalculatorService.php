<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Number;
use Throwable;

class FeeCalculatorService
{
    /**
     * @var array
     */
    private array $fees;

    /**
     * @var mixed
     */
    private mixed $product;

    /**
     * @var string
     */
    private string $supplier;


    /**
     * @param User|null $user
     */
    public function __construct(private readonly ?User $user)
    {
        return $this;
    }

    /**
     * @param int|float $amount
     * @return float
     * @throws Throwable
     */
    public function addFeeTo(int|float $amount): float
    {
        return add_percent($amount, $this->fee());
    }

    /**
     * @return float
     * @throws Throwable
     */
    public function fee(): float
    {
        throw_if(empty($this->supplier) or empty($this->product), 'supplier and product methods must be called');

        if (empty($this->fees)) {
            $this->fees();
        }

        return Number::parseFloat($this->fees[$this->product] ?? $this->user->fee_percentage);
    }

    /**
     * @param $supplier
     * @return $this
     */
    public function supplier($supplier): static
    {
        $this->supplier = $supplier;
        return $this;
    }

    /**
     * @param $product
     * @return $this
     */
    public function product($product): static
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @param $currency
     * @return $this
     */
    public function currency($currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * @return $this
     */
    protected function fees(): static
    {
        $this->fees = $this->user->fees()
            ->where('supplier_name', $this->supplier)
            ->pluck('fee_percentage', 'supplier_id')
            ->toArray();
        return $this;
    }
}
