<?php

namespace App\Services\Suppliers\Factories;

use App\Services\Suppliers\Api\BambooApi;
use App\Services\Suppliers\Contracts\SupplierApiInterface;
use InvalidArgumentException;

class SupplierApiFactory
{
    public static function create(string $supplier): SupplierApiInterface
    {
        return match ($supplier) {
            'bamboo' => new BambooApi,
            default => throw new InvalidArgumentException("Unknown supplier: $supplier"),
        };
    }
}
