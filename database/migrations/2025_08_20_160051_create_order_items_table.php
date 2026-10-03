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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('supplier');
            $table->json('relation');
            $table->unsignedSmallInteger('quantity');
            $table->decimal('purchase_amount');
            $table->decimal('profit_percentage');
            $table->decimal('sale_amount');
            $table->longText('cards')->nullable();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['name', 'supplier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
