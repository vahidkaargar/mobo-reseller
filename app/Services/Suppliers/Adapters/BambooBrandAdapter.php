<?php

namespace App\Services\Suppliers\Adapters;

use App\Services\Suppliers\Contracts\SupplierJsonNormalizerInterface;
use Illuminate\Support\Arr;

class BambooBrandAdapter implements SupplierJsonNormalizerInterface
{
    public function normalize(array $data): array
    {
        return Arr::map($data, function ($item) {
            $item = optional($item);

            return [
                'supplier' => 'bamboo',
                'id' => $item['internalId'],
                'name' => $item['name'],
                'country' => $item['countryCode'],
                'currency' => $item['currencyCode'],
                'image' => $item['logoUrl'],
            ];
        });
    }
}
