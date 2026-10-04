<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bamboo_brands', function (Blueprint $collection) {
            $collection->unique('brand_id');
            $collection->index(['name', 'country', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('bamboo_brands');
    }
};
