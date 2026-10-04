<?php

namespace App\Providers;

use App\Services\CartService;
use App\Services\FeeCalculatorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped, not singleton: both services belong to the authenticated user, so they must be
        // rebuilt per request (queue workers and Octane keep the container alive across requests).
        $this->app->scoped(FeeCalculatorService::class, fn () => new FeeCalculatorService(auth()->user()));

        $this->app->scoped(CartService::class, fn () => new CartService(auth()->user()));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        include app_path('Helpers/helpers.php');
    }
}
