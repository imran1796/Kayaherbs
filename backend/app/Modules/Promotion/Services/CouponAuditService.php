<?php

namespace App\Modules\Promotion\Services;

use App\Modules\Promotion\Repositories\CouponAuditRepository;

class CouponAuditService
{
    public function __construct(
        private readonly CouponAuditRepository $audits
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 10): array
    {
        return $this->audits->recent($limit)
            ->map(fn ($event): array => [
                'event' => $event->event,
                'outcome' => $event->outcome,
                'actor_id' => $event->actor_id,
                'metadata' => $event->metadata ?? [],
                'created_at' => $event->created_at?->toDateTimeString(),
            ])
            ->values()
            ->all();
    }
}
