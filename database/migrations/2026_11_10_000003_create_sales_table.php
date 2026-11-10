<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 32);
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_email', 180)->nullable();
            $table->text('note')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['business_id', 'order_number']);
            $table->index(['business_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
