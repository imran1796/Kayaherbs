<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRate extends Model
{
    protected $fillable = [
        'delivery_zone_id',
        'name',
        'code',
        'amount',
        'min_order_amount',
        'max_order_amount',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_order_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }
}
