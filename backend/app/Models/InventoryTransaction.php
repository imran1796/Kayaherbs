<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'inventory_stock_id',
        'product_variant_id',
        'actor_id',
        'type',
        'quantity_delta',
        'quantity_on_hand_after',
        'quantity_reserved_after',
        'reference_type',
        'reference_id',
        'note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'integer',
            'quantity_on_hand_after' => 'integer',
            'quantity_reserved_after' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
