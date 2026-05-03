<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipment extends Model
{
    protected $fillable = [
        'order_id',
        'created_by_id',
        'carrier_name',
        'tracking_number',
        'tracking_url',
        'status',
        'metadata',
        'shipped_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'shipped_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
