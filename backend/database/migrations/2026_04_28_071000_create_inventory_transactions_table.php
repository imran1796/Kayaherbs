<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->index();
            $table->integer('quantity_delta');
            $table->unsignedInteger('quantity_on_hand_after');
            $table->unsignedInteger('quantity_reserved_after');
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_variant_id', 'created_at']);
            $table->index(['inventory_stock_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
