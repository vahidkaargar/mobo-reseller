<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * decimal(2,2) only stores values up to 0.99, but the default fee is 5 and the
     * admin UI accepts 0.01 to 9.99. Widen both columns to decimal(5,2).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('fee_percentage', 5, 2)->default(5)->change();
        });

        Schema::table('product_fees', function (Blueprint $table) {
            $table->decimal('fee_percentage', 5, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('fee_percentage', 2, 2)->default(5)->change();
        });

        Schema::table('product_fees', function (Blueprint $table) {
            $table->decimal('fee_percentage', 2, 2)->change();
        });
    }
};
