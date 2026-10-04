<?php

use App\Models\BambooBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    BambooBrand::query()->where('brand_id', 990001)->delete();
});

afterEach(function () {
    BambooBrand::query()->where('brand_id', 990001)->delete();
});

test('guests cannot request thumbnails', function () {
    $this->get(route('thumbnail', ['brandId' => 990001, 'w' => 32, 'h' => 32]))
        ->assertRedirect(route('login'));
});

test('unknown brands return 404 without any outbound request', function () {
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->get(route('thumbnail', ['brandId' => 990001, 'w' => 32, 'h' => 32]))
        ->assertNotFound();

    Http::assertNothingSent();
});

test('a url query parameter is ignored and never fetched', function () {
    Http::fake();

    $this->actingAs(User::factory()->create())
        ->get(route('thumbnail', [
            'brandId' => 990001,
            'url' => 'http://169.254.169.254/latest/meta-data/',
            'w' => 32,
            'h' => 32,
        ]))
        ->assertNotFound();

    Http::assertNothingSent();
});

test('the image url is taken from the catalog', function () {
    BambooBrand::query()->create(['brand_id' => 990001, 'name' => 'Test brand', 'image' => 'https://cdn.example.test/logo.png']);
    Http::fake(['cdn.example.test/*' => Http::response('', 500)]);

    $this->actingAs(User::factory()->create())
        ->get(route('thumbnail', ['brandId' => 990001, 'w' => 32, 'h' => 32]))
        ->assertNotFound();

    Http::assertSent(fn ($request) => $request->url() === 'https://cdn.example.test/logo.png');
    Http::assertSentCount(1);
});

test('dimensions are validated', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('thumbnail', ['brandId' => 990001, 'w' => 5000, 'h' => 32]))
        ->assertSessionHasErrors('w');
});
