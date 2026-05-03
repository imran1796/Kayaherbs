<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'name',
        'code',
        'country',
        'states',
        'cities',
        'postal_codes',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'states' => 'array',
            'cities' => 'array',
            'postal_codes' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class);
    }
}
