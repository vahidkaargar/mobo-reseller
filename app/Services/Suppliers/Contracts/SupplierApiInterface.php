<?php

namespace App\Services\Suppliers\Contracts;

interface SupplierApiInterface
{
    public function brands(): array;

    public function brand($brand_id): array;
}
