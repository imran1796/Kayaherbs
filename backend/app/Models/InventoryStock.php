<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStock extends Model
{
    protected $attributes = [
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
        'track_inventory' => true,
        'allow_backorder' => false,
    ];

    protected $fillable = [
        'product_variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'low_stock_threshold',
        'track_inventory',
        'allow_backorder',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'low_stock_threshold' => 'integer',
            'track_inventory' => 'boolean',
            'allow_backorder' => 'boolean',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class)->latest();
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    public function getIsLowStockAttribute(): bool
    {
        if (! $this->track_inventory || $this->low_stock_threshold === null) {
            return false;
        }

        return $this->available_quantity <= $this->low_stock_threshold;
    }

    public function scopeTracked(Builder $query): Builder
    {
        return $query->where('track_inventory', true);
    }

    public function scopeSellableToStorefront(Builder $query): Builder
    {
        return $query->where(function (Builder $stockQuery): void {
            $stockQuery
                ->where('track_inventory', false)
                ->orWhere('allow_backorder', true)
                ->orWhereColumn('quantity_on_hand', '>', 'quantity_reserved');
        });
    }
}
