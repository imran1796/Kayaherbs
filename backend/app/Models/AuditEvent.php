<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'event',
        'outcome',
        'actor_type',
        'actor_id',
        'auditable_type',
        'auditable_id',
        'guard',
        'ip_address',
        'user_agent',
        'request_id',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
