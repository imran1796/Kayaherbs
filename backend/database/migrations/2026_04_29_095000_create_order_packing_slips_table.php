<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_packing_slips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('packing_slip_number')->unique();
            $table->string('status', 40)->default('generated');
            $table->json('shipping_address');
            $table->json('items');
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_packing_slips');
    }
};
