<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Volt\Volt;

class VoltServiceProvider extends ServiceProvider
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
        Volt::mount([
            config('livewire.view_path', resource_path('views/livewire')),
            resource_path('views/pages'),
        ]);

        // Livewire update requests only re-run persistent middleware; a deactivated user
        // must not keep firing component actions from an already open page.
        Livewire::addPersistentMiddleware([EnsureUserIsActive::class]);
    }
}
