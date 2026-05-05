<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->decimal('minimum_order_value', 12, 2)->nullable()->after('discount_value');
            $table->json('eligible_product_ids')->nullable()->after('ends_at');
            $table->json('eligible_category_ids')->nullable()->after('eligible_product_ids');
            $table->unsignedInteger('usage_limit')->nullable()->after('eligible_category_ids');
            $table->unsignedInteger('per_customer_usage_limit')->nullable()->after('usage_limit');
            $table->unsignedInteger('used_count')->default(0)->after('per_customer_usage_limit');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn([
                'minimum_order_value',
                'eligible_product_ids',
                'eligible_category_ids',
                'usage_limit',
                'per_customer_usage_limit',
                'used_count',
            ]);
        });
    }
};
