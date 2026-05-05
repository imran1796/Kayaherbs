<?php

namespace App\Modules\Promotion\Repositories;

use App\Models\AuditEvent;
use Illuminate\Support\Collection;

class CouponAuditRepository
{
    public function recent(int $limit = 10): Collection
    {
        return AuditEvent::query()
            ->whereIn('event', [
                'coupon.created',
                'coupon.updated',
                'coupon.activated',
                'coupon.deactivated',
                'coupon.deleted',
            ])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
