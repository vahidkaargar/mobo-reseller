<?php

namespace App\Services;

use App\Models\ExchangeCurrency;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeService
{
    /**
     * @var string
     */
    protected static string $api = 'https://mobo.gifts/api/bamboo/exchange';

    /**
     * @var array
     */
    public static array $rates = [];

    /**
     * @param string $currency
     * @return array
     */
    public static function rates(string $currency = 'usd'): array
    {
        $currency = strtolower($currency);

        $lock = Cache::lock("exchange.$currency.lock", 120);
        if ($lock->get()) {
            dispatch(fn() => static::save($currency));
        }

        return ExchangeCurrency::query()
            ->where('currency', $currency)
            ->value('rates');
    }

    /**
     * @param string $currency
     * @return void
     */
    protected static function save(string $currency = 'usd'): void
    {
        $exchange_rates = static::latest($currency);
        if (filled($exchange_rates)) {
            $exchange = ExchangeCurrency::query()->firstOrNew(['currency' => $currency]);
            $exchange->rates = $exchange_rates;
            $exchange->updated_at = now();
            $exchange->save();
        }
    }

    /**
     * @param string $currency
     * @return array
     */
    protected static function latest(string $currency = 'usd'): array
    {
        try {
            return Http::retry(3)
                ->get(static::$api, ['base' => $currency])
                ->collect('body.rates')
                ->pluck('value', 'currencyCode')
                ->toArray();
        } catch (ConnectionException $e) {
            return [];
        }
    }
}
