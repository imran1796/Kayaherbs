<?php

namespace App\Modules\Setting\Repositories;

use App\Models\StoreSetting;
use Illuminate\Support\Collection;

class StoreSettingRepository
{
    /**
     * @param  list<string>  $keys
     * @return Collection<string, mixed>
     */
    public function valuesByGroupAndKeys(string $group, array $keys): Collection
    {
        return StoreSetting::query()
            ->where('group', $group)
            ->whereIn('key', $keys)
            ->pluck('value', 'key');
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public function updateOrCreate(array $attributes, array $values): StoreSetting
    {
        return StoreSetting::query()->updateOrCreate($attributes, $values);
    }
}
