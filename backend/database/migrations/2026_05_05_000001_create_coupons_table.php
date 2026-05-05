<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('discount_type');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->string('status')->default('inactive');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique('code');
            $table->index(['status', 'starts_at', 'ends_at']);
            $table->index('discount_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
