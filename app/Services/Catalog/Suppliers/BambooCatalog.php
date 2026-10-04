<?php

namespace App\Services\Catalog\Suppliers;

use App\Http\Resources\BambooBrandResource;
use App\Models\BambooBrand;
use App\Services\Catalog\Contracts\CatalogInterface;

class BambooCatalog implements CatalogInterface
{
    public function categories(): array
    {
        $brands = BambooBrand::query()->orderBy('name')->get();

        return BambooBrandResource::collection($brands)->toArray(request());
    }

    public function products($category_id): array
    {
        return BambooBrand::query()
            ->where('brand_id', intval($category_id))
            ->value('products');
    }

    public function product($product_id): array
    {
        $products = BambooBrand::query()
            ->where('products.id', intval($product_id))
            ->value('products');

        return collect($products)->where('id', $product_id)->first();
    }
}
