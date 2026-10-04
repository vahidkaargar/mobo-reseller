<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class BladeDirectiveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Blade::directive('currency', fn ($expr) => "<?php echo format_currency($expr); ?>");
        Blade::directive('exchange', fn ($expr) => "<?php echo exchange($expr); ?>");
        Blade::directive('percentage', fn ($expr) => "<?php echo $expr . '%'; ?>");
    }
}
