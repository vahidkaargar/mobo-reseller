<?php

namespace App\Services;

use App\Enums\SuppliersEnum;

class CatalogService
{
    public function __construct(private readonly SuppliersEnum $supplier) {}

    public function categories(): array
    {
        return $this->supplier->catalog()->categories();
    }

    public function products($category_id): array
    {
        return $this->supplier->catalog()->products($category_id);
    }
}
