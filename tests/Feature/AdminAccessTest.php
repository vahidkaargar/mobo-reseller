<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

function adminUser(): User
{
    $user = User::factory()->create();
    $user->addRole('admin');

    return $user;
}

dataset('admin pages', [
    'users index' => fn () => route('admin.users.index'),
    'user settings' => fn () => route('admin.users.settings', User::factory()->create()),
    'user fees' => fn () => route('admin.users.fees', User::factory()->create()),
    'brands index' => fn () => route('admin.brands.index'),
]);

test('guests are redirected to login from admin pages', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with('admin pages');

test('verified users without the admin role are forbidden', function (string $url) {
    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertForbidden();
})->with('admin pages');

test('admins can open admin pages', function (string $url) {
    $this->actingAs(adminUser())
        ->get($url)
        ->assertOk();
})->with('admin pages');

test('admin navigation is hidden from non-admins', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('admin.users.index'));
});

test('admin navigation is shown to admins', function () {
    $this->actingAs(adminUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.users.index'));
});

test('grant command adds and revokes the admin role', function () {
    $user = User::factory()->create();

    expect(Artisan::call('app:grant-admin-role', ['email' => $user->email]))->toBe(0)
        ->and($user->fresh()->hasRole('admin'))->toBeTrue();

    expect(Artisan::call('app:grant-admin-role', ['email' => $user->email, '--revoke' => true]))->toBe(0)
        ->and($user->fresh()->hasRole('admin'))->toBeFalse();
});

test('grant command fails for an unknown email', function () {
    expect(Artisan::call('app:grant-admin-role', ['email' => 'missing@example.com']))->toBe(1);
});
