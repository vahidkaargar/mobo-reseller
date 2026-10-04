<?php

namespace App\Services\Suppliers\Contracts;

interface SupplierJsonNormalizerInterface
{
    /**
     * Normalize suppliers JSON data to the standard schema.
     *
     * @param  array  $data  Raw data from the JSON file.
     * @return array Normalized product data (e.g., ['id' => ..., 'name' => ..., 'price' => ...]).
     */
    public function normalize(array $data): array;
}
