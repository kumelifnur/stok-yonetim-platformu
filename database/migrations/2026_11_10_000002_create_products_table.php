<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 50);
            $table->string('name', 140);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->unsignedInteger('stock_on_hand')->default(0);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['business_id', 'sku']);
            $table->index(['business_id', 'is_active', 'stock_on_hand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
