<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shipments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('carrier_name');
            $table->string('tracking_number')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('status', 40)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shipments');
    }
};
