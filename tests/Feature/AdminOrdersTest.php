<?php

use App\Enums\OrderStatusEnum;
use App\Exceptions\OrderResolutionException;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderResolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminForOrders(): User
{
    $admin = User::factory()->create();
    $admin->addRole('admin');

    return $admin;
}

function unsettledOrder(OrderStatusEnum $status = OrderStatusEnum::PROCESSING, float $amount = 19.95): Order
{
    $user = User::factory()->create()->fresh();
    $user->createWallet(['name' => 'USD Wallet', 'slug' => 'usd', 'currency' => 'USD', 'is_active' => true]);
    $user->deposit('usd', 100);

    $order = Order::query()->create([
        'user_id' => $user->id,
        'wallet_id' => $user->getWallet('usd')->id,
        'status' => $status,
        'purchase_amount' => 19,
        'sale_amount' => $amount,
    ]);
    $user->lockFunds('usd', $amount, $order->walletReference());

    return $order->fresh();
}

test('releasing an unsettled order unlocks the funds and marks it failed', function (OrderStatusEnum $status) {
    $order = unsettledOrder($status);

    $resolved = app(OrderResolutionService::class)->release($order, adminForOrders(), 'test');

    $wallet = $order->user->getWallet('usd')->fresh();
    expect($resolved->status)->toBe(OrderStatusEnum::FAILED)
        ->and($resolved->completed_at)->not->toBeNull()
        ->and((float) $wallet->locked)->toBe(0.0)
        ->and((float) $wallet->balance)->toBe(100.0);
})->with([OrderStatusEnum::PROCESSING, OrderStatusEnum::PARTIAL_FAILED]);

test('charging an unsettled order withdraws the funds and marks it succeeded', function () {
    $order = unsettledOrder(OrderStatusEnum::PARTIAL_FAILED);

    $resolved = app(OrderResolutionService::class)->charge($order, adminForOrders(), 'test');

    $wallet = $order->user->getWallet('usd')->fresh();
    expect($resolved->status)->toBe(OrderStatusEnum::SUCCEEDED)
        ->and($resolved->paid_at)->not->toBeNull()
        ->and((float) $wallet->locked)->toBe(0.0)
        ->and((float) $wallet->balance)->toBe(80.05);
});

test('settled orders cannot be resolved again', function (OrderStatusEnum $status) {
    $order = Order::query()->create([
        'user_id' => User::factory()->create()->id,
        'status' => $status,
        'purchase_amount' => 1,
        'sale_amount' => 1,
    ]);

    expect(fn () => app(OrderResolutionService::class)->release($order, adminForOrders()))
        ->toThrow(OrderResolutionException::class);
})->with([OrderStatusEnum::CREATED, OrderStatusEnum::SUCCEEDED, OrderStatusEnum::FAILED]);

test('the admin orders page is admin only', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.orders.index'))
        ->assertForbidden();
});

test('the admin orders page lists orders with their customer', function () {
    $order = unsettledOrder();

    $this->actingAs(adminForOrders())
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSee('#'.$order->id)
        ->assertSee($order->user->email);
});
