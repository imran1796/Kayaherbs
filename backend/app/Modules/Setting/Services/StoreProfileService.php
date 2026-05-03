<?php

namespace App\Modules\Setting\Services;

use App\Core\Services\AuditLogger;
use App\Models\User;
use App\Modules\Setting\Repositories\StoreSettingRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StoreProfileService
{
    /**
     * @var list<string>
     */
    public const KEYS = [
        'name',
        'legal_name',
        'support_email',
        'support_phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
        'currency',
        'timezone',
        'locale',
        'website_url',
        'primary_color',
        'secondary_color',
        'logo_path',
        'logo_dark_path',
        'favicon_path',
        'social_share_image_path',
        'seo_title_template',
        'seo_meta_description',
        'seo_meta_keywords',
        'seo_robots',
        'seo_canonical_base_url',
        'seo_og_image_path',
        'privacy_policy_title',
        'privacy_policy_content',
        'terms_conditions_title',
        'terms_conditions_content',
        'refund_policy_title',
        'refund_policy_content',
        'shipping_policy_title',
        'shipping_policy_content',
    ];

    public function __construct(
        protected AuditLogger $auditLogger,
        protected StoreSettingRepository $settings
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getProfile(): array
    {
        return Cache::remember(
            (string) config('store.settings.cache_key', 'store.settings'),
            (int) config('store.settings.cache_ttl', 300),
            fn (): array => $this->loadProfile()
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateProfile(array $data, ?User $actor = null): array
    {
        return DB::transaction(function () use ($data, $actor): array {
            $payload = Arr::only($data, self::KEYS);
            $before = $this->getProfile();

            foreach (self::KEYS as $key) {
                $this->settings->updateOrCreate(
                    ['key' => 'store.profile.'.$key],
                    [
                        'group' => 'profile',
                        'value' => $payload[$key] ?? null,
                        'type' => 'string',
                        'is_public' => true,
                    ]
                );
            }

            $this->forgetCache();
            $after = $this->getProfile();

            $this->auditLogger->record(
                'store_profile.updated',
                actor: $actor,
                metadata: [
                    'changed' => $this->changedKeys($before, $after),
                ],
                guard: 'web'
            );

            return $after;
        });
    }

    public function forgetCache(): void
    {
        Cache::forget((string) config('store.settings.cache_key', 'store.settings'));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadProfile(): array
    {
        $settings = $this->settings->valuesByGroupAndKeys(
            'profile',
            array_map(fn (string $key): string => 'store.profile.'.$key, self::KEYS)
        );

        $profile = $this->defaults();

        foreach (self::KEYS as $key) {
            $storedKey = 'store.profile.'.$key;

            if ($settings->has($storedKey)) {
                $profile[$key] = $settings->get($storedKey);
            }
        }

        return $profile;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'name' => config('store.defaults.name'),
            'legal_name' => config('store.defaults.name'),
            'support_email' => config('store.defaults.support_email'),
            'support_phone' => null,
            'address_line_1' => null,
            'address_line_2' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => 'BD',
            'currency' => config('store.defaults.currency'),
            'timezone' => config('store.defaults.timezone'),
            'locale' => config('app.locale'),
            'website_url' => config('app.url'),
            'primary_color' => '#0B5FFF',
            'secondary_color' => '#111827',
            'logo_path' => '/storage/branding/logo.svg',
            'logo_dark_path' => '/storage/branding/logo-dark.svg',
            'favicon_path' => '/storage/branding/favicon.ico',
            'social_share_image_path' => '/storage/branding/og-default.jpg',
            'seo_title_template' => '{page_title} | {store_name}',
            'seo_meta_description' => null,
            'seo_meta_keywords' => null,
            'seo_robots' => 'index,follow',
            'seo_canonical_base_url' => config('app.url'),
            'seo_og_image_path' => '/storage/branding/og-default.jpg',
            'privacy_policy_title' => 'Privacy Policy',
            'privacy_policy_content' => null,
            'terms_conditions_title' => 'Terms & Conditions',
            'terms_conditions_content' => null,
            'refund_policy_title' => 'Refund Policy',
            'refund_policy_content' => null,
            'shipping_policy_title' => 'Shipping Policy',
            'shipping_policy_content' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return list<string>
     */
    private function changedKeys(array $before, array $after): array
    {
        $changed = [];

        foreach (self::KEYS as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}
