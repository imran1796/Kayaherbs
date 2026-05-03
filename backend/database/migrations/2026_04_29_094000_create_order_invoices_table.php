<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status', 40)->default('issued');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_total', 12, 2);
            $table->decimal('grand_total', 12, 2);
            $table->string('currency', 3);
            $table->json('billing_address');
            $table->json('shipping_address');
            $table->json('metadata')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_invoices');
    }
};
