<?php

use App\Models\ExchangeCurrency;
use App\Models\User;
use App\Services\CartService;
use App\Services\FeeCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // rates() queues a refresh from mobo.gifts; keep the suite offline.
    Http::fake();
    ExchangeCurrency::query()->where('currency', 'usd')->delete();
});

test('exchange returns the amount unchanged for the same currency even without rates', function () {
    expect(exchange(12.3456, 'usd', 'USD'))->toBe(12.3456)
        ->and(exchange(10, 'USD', 'USD', 2))->toBe(10.0);
});

test('exchange converts with the stored rate', function () {
    ExchangeCurrency::query()->create(['currency' => 'usd', 'rates' => ['EUR' => 1.25]]);

    expect(exchange(10, 'EUR', 'USD'))->toBe(12.5);
});

test('exchange throws when the rate is missing instead of pricing at zero', function () {
    expect(fn () => exchange(10, 'EUR', 'USD'))->toThrow(RuntimeException::class, 'EUR -> USD');

    ExchangeCurrency::query()->create(['currency' => 'usd', 'rates' => ['EUR' => 0]]);
    expect(fn () => exchange(10, 'EUR', 'USD'))->toThrow(RuntimeException::class);
});

test('fee calculator and cart fail clearly without an authenticated user', function () {
    expect(fn () => (new FeeCalculatorService(null))->supplier('BAMBOO')->product('1')->fee())
        ->toThrow(RuntimeException::class, 'authenticated user');

    expect(fn () => (new CartService(null))->items())
        ->toThrow(RuntimeException::class, 'authenticated user');
});

test('fee calculator uses the override for the selected product and the default otherwise', function () {
    $user = User::factory()->create()->fresh();
    $user->fees()->create(['supplier_name' => 'BAMBOO', 'supplier_id' => '42', 'fee_percentage' => 0.5]);

    $calculator = new FeeCalculatorService($user);

    expect($calculator->supplier('BAMBOO')->product('42')->fee())->toBe(0.5)
        ->and($calculator->product('43')->fee())->toBe(5.0)
        ->and($calculator->supplier('OTHER')->product('42')->fee())->toBe(5.0);
});

test('services are rebuilt per request instead of being shared singletons', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs($first);
    $cartForFirst = app(CartService::class);

    app()->forgetScopedInstances();
    $this->actingAs($second);
    $cartForSecond = app(CartService::class);

    expect($cartForFirst)->not->toBe($cartForSecond);
});
