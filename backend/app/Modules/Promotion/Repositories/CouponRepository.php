<?php

namespace App\Modules\Promotion\Repositories;

use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CouponRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Coupon
    {
        return Coupon::query()->create($data);
    }

    public function findOrFail(int $id): Coupon
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::query()->findOrFail($id);

        return $coupon;
    }

    public function findByCodeOrFail(string $code): Coupon
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::query()
            ->where('code', strtoupper($code))
            ->firstOrFail();

        return $coupon;
    }

    public function lockForUpdate(int $id): Coupon
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();

        return $coupon;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($data);

        return $coupon->refresh();
    }

    public function delete(Coupon $coupon): bool
    {
        return (bool) $coupon->delete();
    }

    public function incrementUsedCount(Coupon $coupon): Coupon
    {
        $coupon->increment('used_count');

        return $coupon->refresh();
    }
}
