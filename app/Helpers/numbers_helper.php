<?php

if (!function_exists('add_percent')) {
    function add_percent(int|float $amount, int|float $percent): float
    {
        return $amount * (1 + $percent / 100);
    }
}

if (!function_exists('exchange')) {
    function exchange(int|float $amount, $fromCurrency = 'USD', $toCurrency = 'USD', $precision = 4): float
    {
        $exchangeRates = \App\Services\ExchangeService::rates($toCurrency);
        $exchanged = $amount * floatval($exchangeRates[$fromCurrency]);
        $exchanged = round($exchanged, $precision);
        return \Illuminate\Support\Number::parseFloat($exchanged);
    }
}

if (!function_exists('format_currency')) {
    function format_currency(int|float $amount, $currency = 'USD'): false|string
    {
        $float = str($amount)->contains('.') ? explode('.', $amount)[1] : 0;
        $float = rtrim($float, '0');
        $precision = (int)$float !== 0 ? strlen($float) : 0;
        return \Illuminate\Support\Number::currency($amount, $currency, precision: $precision);
    }
}
