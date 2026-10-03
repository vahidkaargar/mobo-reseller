<?php

use App\Http\Controllers\ThumbnailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::get('/thumbnail', ThumbnailController::class)->name('thumbnail');


Route::middleware(['auth', 'verified'])->group(function () {

    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Settings
    Route::redirect('settings', '/settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Volt::route('settings/2fa', 'settings.2fa')->middleware(['password.confirm'])->name('settings.2fa');


    // Developers
    Route::redirect('developers', '/developers/tokens');
    Volt::route('developers/tokens', 'developers.tokens')->name('developers.tokens');
    Volt::route('developers/documentation', 'developers.documentation')->name('developers.documentation');


    Volt::route('/orders', 'orders.index')->name('orders.index');
    Volt::route('/orders/create', 'orders.create')->name('orders.create');
    Volt::route('/wallets', 'wallets.index')->name('wallets.index');


    // admin routes
    Route::middleware([])->prefix('/admin')->name('admin.')->group(function () {

        Volt::route('/users', 'admin.users.index')->name('users.index');
        Volt::route('/users/{user}', 'admin.users.show')->name('users.show');
        Volt::route('/users/{user}/settings', 'admin.users.settings')->name('users.settings');
        Volt::route('/users/{user}/fees', 'admin.users.fees')->name('users.fees');

        Volt::route('/orders', 'admin.orders.index')->name('orders.index');
        Volt::route('/brands', 'admin.brands.index')->name('brands.index');
        Volt::route('/brands/{brand}', 'admin.brands.show')->name('brands.show');

    });

});

require __DIR__ . '/auth.php';
