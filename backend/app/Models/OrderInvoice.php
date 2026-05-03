<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderInvoice extends Model
{
    protected $fillable = [
        'order_id',
        'issued_by_id',
        'invoice_number',
        'status',
        'subtotal',
        'shipping_total',
        'grand_total',
        'currency',
        'billing_address',
        'shipping_address',
        'metadata',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'metadata' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }
}
