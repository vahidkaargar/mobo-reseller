<?php

namespace App\Services\Suppliers\Integrations;

use App\Services\Suppliers\Factories\BrandAdapterFactory;
use App\Services\Suppliers\Factories\SupplierApiFactory;

class BrandIntegrationService
{
    public function integrate(array $suppliers): array
    {
        // TODO: you can cache output

        $integratedBrands = [];
        foreach ($suppliers as $supplier) {

            // get brands from supplier api
            $data = SupplierApiFactory::create($supplier)->brands();

            // integrated suppliers brands into a unified JSON output with a specific schema
            $normalized = BrandAdapterFactory::create($supplier)->normalize($data);

            $integratedBrands = array_merge($integratedBrands, $normalized);
        }

        return $integratedBrands;
    }
}
