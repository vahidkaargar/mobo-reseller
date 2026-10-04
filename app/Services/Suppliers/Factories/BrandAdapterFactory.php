<?php

namespace App\Services\Suppliers\Factories;

use App\Services\Suppliers\Adapters\BambooBrandAdapter;
use App\Services\Suppliers\Contracts\SupplierJsonNormalizerInterface;
use InvalidArgumentException;

class BrandAdapterFactory
{
    public static function create(string $supplier): SupplierJsonNormalizerInterface
    {
        return match ($supplier) {
            'bamboo' => new BambooBrandAdapter,
            default => throw new InvalidArgumentException("Unknown supplier: $supplier"),
        };
    }
}
