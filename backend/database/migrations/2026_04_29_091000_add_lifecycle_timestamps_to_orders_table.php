<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->timestamp('confirmed_at')->nullable()->after('placed_at');
            $table->timestamp('processing_at')->nullable()->after('confirmed_at');
            $table->timestamp('packed_at')->nullable()->after('processing_at');
            $table->timestamp('shipped_at')->nullable()->after('packed_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('failed_delivery_at')->nullable()->after('delivered_at');
            $table->timestamp('return_requested_at')->nullable()->after('failed_delivery_at');
            $table->timestamp('returned_at')->nullable()->after('return_requested_at');
            $table->timestamp('refunded_at')->nullable()->after('returned_at');
            $table->timestamp('cancelled_at')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'confirmed_at',
                'processing_at',
                'packed_at',
                'shipped_at',
                'delivered_at',
                'failed_delivery_at',
                'return_requested_at',
                'returned_at',
                'refunded_at',
                'cancelled_at',
            ]);
        });
    }
};
