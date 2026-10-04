<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function inactiveUser(): User
{
    $user = User::factory()->create();
    $user->forceFill(['is_active' => false])->save();

    return $user->fresh();
}

test('an inactive user is logged out and sent to login', function () {
    $this->actingAs(inactiveUser())
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('an inactive user cannot reach settings or orders', function (string $route) {
    $this->actingAs(inactiveUser())
        ->get(route($route))
        ->assertRedirect(route('login'));

    $this->assertGuest();
})->with(['settings.profile', 'orders.create', 'wallets.index']);

test('an active user is unaffected', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('deactivating a user ends their current session on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
