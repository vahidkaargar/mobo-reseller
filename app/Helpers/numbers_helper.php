<?php

use App\Services\ExchangeService;
use Illuminate\Support\Number;

if (! function_exists('add_percent')) {
    function add_percent(int|float $amount, int|float $percent): float
    {
        return $amount * (1 + $percent / 100);
    }
}

if (! function_exists('exchange')) {
    /**
     * Convert an amount between currencies using the cached rates for the target currency.
     *
     * A missing rate throws instead of silently pricing the amount at 0.
     *
     * @throws RuntimeException
     */
    function exchange(int|float $amount, string $fromCurrency = 'USD', string $toCurrency = 'USD', int $precision = 4): float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return round((float) $amount, $precision);
        }

        $rate = ExchangeService::rates($toCurrency)[$fromCurrency] ?? null;
        if (! is_numeric($rate) || (float) $rate <= 0) {
            throw new RuntimeException("No exchange rate for $fromCurrency -> $toCurrency.");
        }

        return round($amount * (float) $rate, $precision);
    }
}

if (! function_exists('format_currency')) {
    function format_currency(int|float $amount, $currency = 'USD'): false|string
    {
        $float = str($amount)->contains('.') ? explode('.', $amount)[1] : 0;
        $float = rtrim($float, '0');
        $precision = (int) $float !== 0 ? strlen($float) : 0;

        return Number::currency($amount, $currency, precision: $precision);
    }
}
