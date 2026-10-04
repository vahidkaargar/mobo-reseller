<?php

namespace App\Facades;

use App\Services\CartService;
use Illuminate\Support\Facades\Facade;

/**
 * @mixin CartService
 */
class Cart extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CartService::class;
    }
}
