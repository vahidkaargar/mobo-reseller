<?php

namespace App\Facades;

use App\Services\FeeCalculatorService;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin FeeCalculatorService
 */
class FeeCalculator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return FeeCalculatorService::class;
    }
}
