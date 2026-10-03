<?php

namespace App\Services\Catalog\Contracts;

interface CatalogInterface
{
    public function categories(): array;

    public function products($category_id): array;

    public function product($product_id): array;
}
