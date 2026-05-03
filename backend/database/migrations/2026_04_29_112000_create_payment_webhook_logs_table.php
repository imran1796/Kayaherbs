<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 80);
            $table->string('event_id')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->string('status', 40);
            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
            $table->unique(['provider', 'payload_hash']);
            $table->index(['provider', 'status']);
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
