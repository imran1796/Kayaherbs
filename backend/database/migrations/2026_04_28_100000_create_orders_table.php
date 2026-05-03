<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idempotency_key', 120);
            $table->string('status', 40)->default('pending');
            $table->string('payment_status', 40)->default('pending');
            $table->string('fulfillment_status', 40)->default('unfulfilled');
            $table->string('shipping_method_code', 80);
            $table->string('shipping_method_name');
            $table->string('payment_method_code', 80);
            $table->string('payment_method_name');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2);
            $table->decimal('grand_total', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->json('shipping_address');
            $table->json('billing_address');
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
