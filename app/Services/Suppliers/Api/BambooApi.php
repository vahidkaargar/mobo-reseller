<?php

namespace App\Services\Suppliers\Api;

use App\Services\Suppliers\Contracts\SupplierApiInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class BambooApi implements SupplierApiInterface
{
    protected string $baseUrl = 'https://mobo.gifts/api/bamboo';

    public function brands(): array
    {
        try {

            $request = $this->http()->get('/brands', [
                'fetch' => 'all',
                'select' => 'internalId,name,countryCode,currencyCode,logoUrl',
            ]);

            return $request->successful()
                ? $request->collect()->toArray()
                : [];

        } catch (ConnectionException) {
            return [];
        }
    }

    public function brand($brand_id): array
    {
        try {

            $request = $this->http()->get('/catalog', [
                'target_currency' => 'USD',
                'brand_id' => $brand_id,
            ]);

            return $request->successful()
                ? ($request->collect()->toArray()['body']['items'][0] ?? [])
                : [];

        } catch (ConnectionException) {
            return [];
        }
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()->baseUrl($this->baseUrl);
    }
}
