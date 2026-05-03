<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPackingSlip extends Model
{
    protected $fillable = [
        'order_id',
        'generated_by_id',
        'packing_slip_number',
        'status',
        'shipping_address',
        'items',
        'metadata',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'items' => 'array',
            'metadata' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_id');
    }
}
