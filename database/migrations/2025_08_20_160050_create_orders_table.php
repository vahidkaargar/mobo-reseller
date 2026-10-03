<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id()->startingValue(1000);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('CREATED');
            $table->decimal('purchase_amount');
            $table->decimal('sale_amount');
            $table->timestamp('paid_at');
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['user_id', 'wallet_id']);
            $table->index(['status', 'paid_at', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
