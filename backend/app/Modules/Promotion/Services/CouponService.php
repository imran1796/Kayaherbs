<?php

namespace App\Modules\Promotion\Services;

use App\Core\Services\AuditLogger;
use App\Models\Coupon;
use App\Models\User;
use App\Modules\Promotion\Repositories\CouponRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CouponService
{
    public function __construct(
        private readonly CouponRepository $coupons,
        private readonly AuditLogger $auditLogger
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->coupons->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Coupon
    {
        $coupon = $this->coupons->create($this->payload($data));

        $this->audit('coupon.created', $coupon, $actor, [
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'status' => $coupon->status,
        ]);

        return $coupon;
    }

    public function findOrFail(int $id): Coupon
    {
        return $this->coupons->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data, ?User $actor = null): Coupon
    {
        $coupon = $this->findOrFail($id);
        $before = $this->auditSnapshot($coupon);
        $updated = $this->coupons->update($coupon, $this->payload($data));

        $this->audit('coupon.updated', $updated, $actor, [
            'code' => $updated->code,
            'changed' => $this->changedKeys($before, $this->auditSnapshot($updated)),
        ]);

        return $updated;
    }

    public function activate(int $id, ?User $actor = null): Coupon
    {
        $coupon = $this->findOrFail($id);
        $updated = $this->coupons->update($coupon, ['status' => Coupon::STATUS_ACTIVE]);

        $this->audit('coupon.activated', $updated, $actor, [
            'code' => $updated->code,
            'status' => $updated->status,
        ]);

        return $updated;
    }

    public function deactivate(int $id, ?User $actor = null): Coupon
    {
        $coupon = $this->findOrFail($id);
        $updated = $this->coupons->update($coupon, ['status' => Coupon::STATUS_INACTIVE]);

        $this->audit('coupon.deactivated', $updated, $actor, [
            'code' => $updated->code,
            'status' => $updated->status,
        ]);

        return $updated;
    }

    public function delete(int $id, ?User $actor = null): void
    {
        $coupon = $this->findOrFail($id);
        $snapshot = $this->auditSnapshot($coupon);
        $this->coupons->delete($coupon);

        $this->auditLogger->record(
            'coupon.deleted',
            actor: $actor,
            metadata: $snapshot,
            guard: $actor?->currentAccessToken() === null ? 'web' : 'sanctum'
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        $payload = Arr::only($data, [
            'name',
            'code',
            'discount_type',
            'discount_value',
            'minimum_order_value',
            'status',
            'starts_at',
            'ends_at',
            'eligible_product_ids',
            'eligible_category_ids',
            'usage_limit',
            'per_customer_usage_limit',
            'used_count',
        ]);

        $payload['code'] = Str::upper((string) $payload['code']);

        if (($payload['discount_type'] ?? null) === Coupon::DISCOUNT_FREE_DELIVERY) {
            $payload['discount_value'] = 0;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function auditSnapshot(Coupon $coupon): array
    {
        return [
            'code' => $coupon->code,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'minimum_order_value' => $coupon->minimum_order_value,
            'status' => $coupon->status,
            'starts_at' => $coupon->starts_at?->toDateTimeString(),
            'ends_at' => $coupon->ends_at?->toDateTimeString(),
            'usage_limit' => $coupon->usage_limit,
            'per_customer_usage_limit' => $coupon->per_customer_usage_limit,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(string $event, Coupon $coupon, ?User $actor, array $metadata): void
    {
        $this->auditLogger->record(
            $event,
            actor: $actor,
            auditable: $coupon,
            metadata: $metadata,
            guard: $actor?->currentAccessToken() === null ? 'web' : 'sanctum'
        );
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    private function changedKeys(array $before, array $after): array
    {
        return collect(array_keys($after))
            ->filter(fn (string $key): bool => ($before[$key] ?? null) !== ($after[$key] ?? null))
            ->values()
            ->all();
    }
}
