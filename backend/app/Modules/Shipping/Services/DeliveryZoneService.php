<?php

namespace App\Modules\Shipping\Services;

use App\Models\DeliveryZone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeliveryZoneService
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return DeliveryZone::query()
            ->with('rates')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DeliveryZone
    {
        return DeliveryZone::query()->create($data)->load('rates');
    }

    public function findOrFail(int $id): DeliveryZone
    {
        return DeliveryZone::query()->with('rates')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): DeliveryZone
    {
        $zone = $this->findOrFail($id);
        $zone->update($data);

        return $zone->refresh()->load('rates');
    }

    public function delete(int $id): void
    {
        $this->findOrFail($id)->delete();
    }
}
