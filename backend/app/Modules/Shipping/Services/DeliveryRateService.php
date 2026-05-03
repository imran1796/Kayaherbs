<?php

namespace App\Modules\Shipping\Services;

use App\Models\DeliveryRate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryRateService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return DeliveryRate::query()
            ->with('zone')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeliveryRate
    {
        return DeliveryRate::query()->create($data)->load('zone');
    }

    public function findOrFail(int $id): DeliveryRate
    {
        return DeliveryRate::query()->with('zone')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): DeliveryRate
    {
        $rate = $this->findOrFail($id);
        $rate->update($data);

        return $rate->refresh()->load('zone');
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}
