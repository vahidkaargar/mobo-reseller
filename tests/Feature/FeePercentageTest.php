<?php

use App\Models\User;
use App\Services\FeeCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('fee percentage columns hold values up to 999.99', function (string $table) {
    $column = collect(Schema::getColumns($table))->firstWhere('name', 'fee_percentage');

    expect($column['type'])->toContain('5,2');
})->with(['users', 'product_fees'])->skip(fn () => Schema::getConnection()->getDriverName() === 'sqlite', 'SQLite does not keep decimal precision in the column type');

test('new users get the default 5 percent fee', function () {
    $user = User::factory()->create()->fresh();

    expect((float) $user->fee_percentage)->toBe(5.0)
        ->and((new FeeCalculatorService($user))->supplier('BAMBOO')->product('123')->addFeeTo(100))->toBe(105.0);
});

test('admin fee values up to 9.99 round-trip', function () {
    $user = User::factory()->create();
    $user->forceFill(['fee_percentage' => 9.99])->save();
    $user->fees()->create(['supplier_name' => 'BAMBOO', 'supplier_id' => '123', 'fee_percentage' => 7.25]);

    $calculator = new FeeCalculatorService($user->fresh());

    expect($calculator->supplier('BAMBOO')->product('123')->fee())->toBe(7.25)
        ->and((new FeeCalculatorService($user->fresh()))->supplier('BAMBOO')->product('999')->fee())->toBe(9.99);
});
