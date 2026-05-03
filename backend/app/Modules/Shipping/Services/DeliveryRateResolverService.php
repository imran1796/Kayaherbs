<?php

namespace App\Modules\Shipping\Services;

use App\Models\CustomerAddress;
use App\Models\DeliveryRate;
use App\Models\DeliveryZone;

class DeliveryRateResolverService
{
    /**
     * @return array<string, mixed>|null
     */
    public function resolve(CustomerAddress $address, float $subtotal, ?string $requestedCode = null): ?array
    {
        if (! $this->hasConfiguredRates()) {
            return null;
        }

        $zone = $this->matchingZone($address);

        if ($zone === null) {
            return null;
        }

        $rate = $zone->rates()
            ->where('status', 'active')
            ->when($requestedCode !== null, fn ($query) => $query->where('code', $requestedCode))
            ->where(function ($query) use ($subtotal): void {
                $query->whereNull('min_order_amount')->orWhere('min_order_amount', '<=', $subtotal);
            })
            ->where(function ($query) use ($subtotal): void {
                $query->whereNull('max_order_amount')->orWhere('max_order_amount', '>=', $subtotal);
            })
            ->orderBy('sort_order')
            ->orderBy('amount')
            ->first();

        if ($rate === null) {
            return null;
        }

        return $this->payload($rate, $zone);
    }

    public function hasConfiguredRates(): bool
    {
        return DeliveryRate::query()->where('status', 'active')->exists();
    }

    private function matchingZone(CustomerAddress $address): ?DeliveryZone
    {
        return DeliveryZone::query()
            ->where('status', 'active')
            ->where('country', strtoupper((string) $address->country))
            ->with('rates')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->first(fn (DeliveryZone $zone): bool => $this->zoneMatches($zone, $address));
    }

    private function zoneMatches(DeliveryZone $zone, CustomerAddress $address): bool
    {
        return $this->listMatches($zone->states, $address->state)
            && $this->listMatches($zone->cities, $address->city)
            && $this->listMatches($zone->postal_codes, $address->postal_code);
    }

    /**
     * @param  array<int, string>|null  $allowed
     */
    private function listMatches(?array $allowed, ?string $value): bool
    {
        if ($allowed === null || $allowed === []) {
            return true;
        }

        $normalizedAllowed = array_map(
            fn ($item): string => mb_strtolower(trim((string) $item)),
            $allowed
        );

        if (array_intersect($normalizedAllowed, ['all', 'any', '*']) !== []) {
            return true;
        }

        $normalizedValue = mb_strtolower(trim((string) $value));

        return in_array($normalizedValue, $normalizedAllowed, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(DeliveryRate $rate, DeliveryZone $zone): array
    {
        return [
            'code' => $rate->code,
            'name' => $rate->name,
            'amount' => number_format((float) $rate->amount, 2, '.', ''),
            'zone' => [
                'id' => $zone->id,
                'code' => $zone->code,
                'name' => $zone->name,
                'country' => $zone->country,
            ],
        ];
    }
}
