<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'cart_id',
        'coupon_id',
        'coupon_code',
        'idempotency_key',
        'status',
        'payment_status',
        'fulfillment_status',
        'shipping_method_code',
        'shipping_method_name',
        'payment_method_code',
        'payment_method_name',
        'subtotal',
        'shipping_total',
        'discount_total',
        'grand_total',
        'currency',
        'shipping_address',
        'billing_address',
        'placed_at',
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
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'placed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'processing_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_delivery_at' => 'datetime',
            'return_requested_at' => 'datetime',
            'returned_at' => 'datetime',
            'refunded_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class)->latest();
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(OrderReturnRequest::class)->latest();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(OrderInvoice::class);
    }

    public function packingSlip(): HasOne
    {
        return $this->hasOne(OrderPackingSlip::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(OrderShipment::class)->latest();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function scopePlacedBetween(Builder $query, ?string $from = null, ?string $to = null): Builder
    {
        if ($from !== null) {
            $query->whereDate('placed_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('placed_at', '<=', $to);
        }

        return $query;
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '!=', 'cancelled');
    }
}
