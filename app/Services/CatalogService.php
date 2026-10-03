<?php

namespace App\Services;


use App\Enums\SuppliersEnum;

class CatalogService
{
    private SuppliersEnum $supplier;

    public function __construct(SuppliersEnum $suppliersEnum)
    {
        $this->supplier = $suppliersEnum;
        return $this;
    }

    public function categories(): array
    {
        return $this->supplier->catalog()->categories();
    }

    public function products($category_id): array
    {
        return $this->supplier->catalog()->products($category_id);
    }
}
