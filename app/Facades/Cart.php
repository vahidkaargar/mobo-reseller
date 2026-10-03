<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Services\CartService;

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
