<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const DISCOUNT_FIXED = 'fixed';
    public const DISCOUNT_PERCENTAGE = 'percentage';
    public const DISCOUNT_FREE_DELIVERY = 'free_delivery';

    protected $fillable = [
        'name',
        'code',
        'discount_type',
        'discount_value',
        'minimum_order_value',
        'status',
        'starts_at',
        'ends_at',
        'eligible_product_ids',
        'eligible_category_ids',
        'usage_limit',
        'per_customer_usage_limit',
        'used_count',
    ];

    protected $appends = [
        'lifecycle_status',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'minimum_order_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'eligible_product_ids' => 'array',
            'eligible_category_ids' => 'array',
            'usage_limit' => 'integer',
            'per_customer_usage_limit' => 'integer',
            'used_count' => 'integer',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return self::STATUS_INACTIVE;
        }

        $now = Carbon::now();

        if ($this->starts_at !== null && $this->starts_at->greaterThan($now)) {
            return 'scheduled';
        }

        if ($this->ends_at !== null && $this->ends_at->lessThanOrEqualTo($now)) {
            return 'expired';
        }

        return self::STATUS_ACTIVE;
    }

    public function isCurrentlyActive(): bool
    {
        return $this->lifecycle_status === self::STATUS_ACTIVE;
    }
}
