<?php

use App\Enums\OrderStatusEnum;
use App\Exceptions\CheckoutException;
use App\Jobs\PlaceSupplierOrder;
use App\Jobs\SyncSupplierOrder;
use App\Models\BambooBrand;
use App\Models\ExchangeCurrency;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const TEST_BRAND_ID = 990100;
const TEST_PRODUCT_ID = 990101;

beforeEach(function () {
    Bus::fake();
    Http::preventStrayRequests();

    BambooBrand::query()->where('brand_id', TEST_BRAND_ID)->delete();
    BambooBrand::query()->create([
        'brand_id' => TEST_BRAND_ID,
        'name' => 'Test brand',
        'country' => 'US',
        'currency' => 'USD',
        'products' => [[
            'id' => TEST_PRODUCT_ID,
            'name' => 'Test card 10 USD',
            'country' => 'US',
            'purchase' => ['fixed' => true, 'min' => 10, 'max' => 10, 'currency' => 'USD'],
            'sale' => ['fixed' => true, 'min' => 9.5, 'max' => 9.5, 'unit' => 9.5, 'currency' => 'USD'],
            'discount' => 5,
        ]],
    ]);
    ExchangeCurrency::query()->updateOrCreate(['currency' => 'usd'], ['rates' => ['USD' => 1]]);
});

afterEach(function () {
    BambooBrand::query()->where('brand_id', TEST_BRAND_ID)->delete();
});

function reseller(float $balance = 100): User
{
    $user = User::factory()->create();
    $user->forceFill(['can_place_order' => true, 'is_active' => true])->save();
    $user->createWallet(['name' => 'USD Wallet', 'slug' => 'usd', 'currency' => 'USD', 'is_active' => true]);
    if ($balance > 0) {
        $user->deposit('usd', $balance);
    }

    return $user->fresh();
}

function fillCart(User $user, int $quantity = 2): void
{
    (new CartService($user))->add('BAMBOO', TEST_PRODUCT_ID, 10, $quantity);
}

function placedOrder(User $user): Order
{
    fillCart($user);

    return app(CheckoutService::class)->checkout($user, 'usd');
}

function bambooOrderResponse(array $body): void
{
    Http::fake(['*/orders/*' => Http::response($body)]);
}

test('checkout creates the order, locks the sale amount and empties the cart', function () {
    $user = reseller();

    $order = placedOrder($user);

    // 2 x 9.50 cost, plus the default 5% fee.
    expect($order->status)->toBe(OrderStatusEnum::CREATED)
        ->and((float) $order->purchase_amount)->toBe(19.0)
        ->and((float) $order->sale_amount)->toBe(19.95)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->relation)->toMatchArray(['product_id' => TEST_PRODUCT_ID, 'face_value' => 10])
        ->and((float) $user->getWallet('usd')->fresh()->locked)->toBe(19.95)
        ->and((new CartService($user))->items())->toBe([]);

    Bus::assertDispatched(PlaceSupplierOrder::class, fn ($job) => $job->orderId === $order->id);
});

test('checkout is refused', function (Closure $setup, string $message) {
    $user = reseller();
    fillCart($user);
    $setup($user);

    expect(fn () => app(CheckoutService::class)->checkout($user->fresh(), 'usd'))
        ->toThrow(CheckoutException::class, $message);

    expect(Order::query()->count())->toBe(0)
        ->and((float) $user->getWallet('usd')->fresh()->locked)->toBe(0.0);
    Bus::assertNotDispatched(PlaceSupplierOrder::class);
})->with([
    'inactive account' => [fn (User $u) => $u->forceFill(['is_active' => false])->save(), 'inactive'],
    'ordering disabled' => [fn (User $u) => $u->forceFill(['can_place_order' => false])->save(), 'not allowed'],
    'empty cart' => [fn (User $u) => (new CartService($u))->empty(), 'empty'],
    'insufficient balance' => [fn (User $u) => $u->withdraw('usd', 90), 'Insufficient'],
]);

test('checkout requires a USD wallet', function () {
    $user = reseller();
    $user->createWallet(['name' => 'EUR Wallet', 'slug' => 'eur', 'currency' => 'EUR', 'is_active' => true]);
    fillCart($user);

    expect(fn () => app(CheckoutService::class)->checkout($user, 'eur'))
        ->toThrow(CheckoutException::class, 'USD wallet');
});

test('placing the order sends it to Bamboo and starts polling', function () {
    config(['services.bamboo.account_id' => 123]);
    $order = placedOrder(reseller());
    Http::fake(['*/orders/checkout' => Http::response(['ok' => true])]);

    (new PlaceSupplierOrder($order->id))->handle();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::PROCESSING);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'orders/checkout')
        && $request['RequestId'] === 'mobo-order-'.$order->id
        && $request['AccountId'] === 123
        && $request['Products'] === [['ProductId' => TEST_PRODUCT_ID, 'Quantity' => 2, 'Value' => 10]]);
    Bus::assertDispatched(SyncSupplierOrder::class, fn ($job) => $job->orderId === $order->id && $job->attempt === 1);
});

test('an order that never reached Bamboo releases the funds', function () {
    config(['services.bamboo.account_id' => null]);
    $user = reseller();
    $order = placedOrder($user);
    $job = new PlaceSupplierOrder($order->id);
    Http::fake();

    expect(fn () => $job->handle())->toThrow(RuntimeException::class);
    $job->failed(null);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::FAILED)
        ->and((float) $user->getWallet('usd')->fresh()->locked)->toBe(0.0)
        ->and((float) $user->getWallet('usd')->fresh()->balance)->toBe(100.0);
    Http::assertNothingSent();
});

test('a succeeded Bamboo order stores the cards and charges the wallet', function () {
    $user = reseller();
    $order = placedOrder($user);
    $order->update(['status' => OrderStatusEnum::PROCESSING]);
    bambooOrderResponse(['status' => 'Succeeded', 'items' => [[
        'productId' => TEST_PRODUCT_ID,
        'cards' => [['cardCode' => 'AAA', 'pin' => '1'], ['cardCode' => 'BBB', 'pin' => '2']],
    ]]]);

    (new SyncSupplierOrder($order->id))->handle();

    $order->refresh();
    $wallet = $user->getWallet('usd')->fresh();
    expect($order->status)->toBe(OrderStatusEnum::SUCCEEDED)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->items->first()->cards)->toHaveCount(2)
        ->and((float) $wallet->locked)->toBe(0.0)
        ->and((float) $wallet->balance)->toBe(80.05);
});

test('a failed Bamboo order releases the funds', function () {
    $user = reseller();
    $order = placedOrder($user);
    $order->update(['status' => OrderStatusEnum::PROCESSING]);
    bambooOrderResponse(['status' => 'Failed', 'items' => []]);

    (new SyncSupplierOrder($order->id))->handle();

    $wallet = $user->getWallet('usd')->fresh();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::FAILED)
        ->and((float) $wallet->locked)->toBe(0.0)
        ->and((float) $wallet->balance)->toBe(100.0);
});

test('an incomplete delivery keeps the funds locked for review', function (array $body) {
    $user = reseller();
    $order = placedOrder($user);
    $order->update(['status' => OrderStatusEnum::PROCESSING]);
    bambooOrderResponse($body);

    (new SyncSupplierOrder($order->id))->handle();

    $wallet = $user->getWallet('usd')->fresh();
    expect($order->fresh()->status)->toBe(OrderStatusEnum::PARTIAL_FAILED)
        ->and((float) $wallet->locked)->toBe(19.95)
        ->and((float) $wallet->balance)->toBe(100.0);
})->with([
    'partial failure' => [['status' => 'PartialFailed', 'items' => [['productId' => TEST_PRODUCT_ID, 'cards' => [['cardCode' => 'AAA']]]]]],
    'succeeded without cards' => [['status' => 'Succeeded', 'items' => [['productId' => TEST_PRODUCT_ID]]]],
]);

test('a pending Bamboo order is polled again until the attempt limit', function () {
    $user = reseller();
    $order = placedOrder($user);
    $order->update(['status' => OrderStatusEnum::PROCESSING]);
    bambooOrderResponse(['status' => 'Pending']);

    (new SyncSupplierOrder($order->id, 3))->handle();
    Bus::assertDispatched(SyncSupplierOrder::class, fn ($job) => $job->attempt === 4);

    (new SyncSupplierOrder($order->id, SyncSupplierOrder::MAX_ATTEMPTS))->handle();
    Bus::assertNotDispatched(SyncSupplierOrder::class, fn ($job) => $job->attempt > SyncSupplierOrder::MAX_ATTEMPTS);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::PROCESSING)
        ->and((float) $user->getWallet('usd')->fresh()->locked)->toBe(19.95);
});

test('the orders page lists only the user\'s orders', function () {
    $user = reseller();
    $order = placedOrder($user);
    $other = placedOrder(reseller());

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('#'.$order->id)
        ->assertDontSee('#'.$other->id);
});
