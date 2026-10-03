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
        // Singleton but user is resolved dynamically per request
        $this->app->singleton(FeeCalculatorService::class, function ($app) {
            return new FeeCalculatorService(auth()->user());
        });

        $this->app->singleton(CartService::class, function ($app) {
            return new CartService(auth()->user());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        include app_path('Helpers/helpers.php');
    }
}
