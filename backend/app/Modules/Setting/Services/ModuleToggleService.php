<?php

namespace App\Modules\Setting\Services;

use App\Core\Services\AuditLogger;
use App\Models\User;
use App\Modules\Setting\Repositories\StoreSettingRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ModuleToggleService
{
    /**
     * @var list<string>
     */
    public const MODULES = [
        'catalog',
        'inventory',
        'checkout',
        'coupons',
        'reviews',
        'blog',
        'category',

    ];

    public function __construct(
        protected AuditLogger $auditLogger,
        protected StoreSettingRepository $settings
    ) {}

    /**
     * @return array<string, bool>
     */
    public function getToggles(): array
    {
        return Cache::remember(
            $this->cacheKey(),
            (int) config('store.settings.cache_ttl', 300),
            fn (): array => $this->loadToggles()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, bool>
     */
    public function updateToggles(array $data, ?User $actor = null): array
    {
        return DB::transaction(function () use ($data, $actor): array {
            $before = $this->getToggles();
            $payload = Arr::only($data, self::MODULES);

            foreach (self::MODULES as $module) {
                $value = (bool) ($payload[$module] ?? false);

                $this->settings->updateOrCreate(
                    ['key' => 'store.module.'.$module.'_enabled'],
                    [
                        'group' => 'module',
                        'value' => $value,
                        'type' => 'boolean',
                        'is_public' => false,
                    ]
                );
            }

            $this->forgetCache();
            $after = $this->getToggles();

            $this->auditLogger->record(
                'module_toggles.updated',
                actor: $actor,
                metadata: [
                    'changed' => $this->changedKeys($before, $after),
                ],
                guard: 'web'
            );

            return $after;
        });
    }

    public function enabled(string $module): bool
    {
        return $this->getToggles()[$module] ?? false;
    }

    public function forgetCache(): void
    {
        Cache::forget($this->cacheKey());
    }

    /**
     * @return array<string, bool>
     */
    private function loadToggles(): array
    {
        $keys = array_map(
            fn (string $module): string => 'store.module.'.$module.'_enabled',
            self::MODULES
        );

        $settings = $this->settings->valuesByGroupAndKeys('module', $keys);
        $toggles = $this->defaults();

        foreach (self::MODULES as $module) {
            $storedKey = 'store.module.'.$module.'_enabled';
            if ($settings->has($storedKey)) {
                $toggles[$module] = (bool) $settings->get($storedKey);
            }
        }

        return $toggles;
    }

    /**
     * @return array<string, bool>
     */
    private function defaults(): array
    {
        return [
            'catalog' => true,
            'inventory' => true,
            'checkout' => true,
            'coupons' => false,
            'reviews' => true,
            'blog' => false,
            'category' => true,
        ];
    }

    /**
     * @param  array<string, bool>  $before
     * @param  array<string, bool>  $after
     * @return list<string>
     */
    private function changedKeys(array $before, array $after): array
    {
        $changed = [];

        foreach (self::MODULES as $module) {
            if (($before[$module] ?? false) !== ($after[$module] ?? false)) {
                $changed[] = $module;
            }
        }

        return $changed;
    }

    private function cacheKey(): string
    {
        return (string) config('store.settings.cache_key', 'store.settings').'.modules';
    }
}
