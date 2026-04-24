<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnvironmentConfigurationTest extends TestCase
{
    public function test_store_configuration_defaults_are_available(): void
    {
        $this->assertSame(config('app.name'), config('store.defaults.name'));
        $this->assertSame('BDT', config('store.defaults.currency'));
        $this->assertSame(config('app.timezone'), config('store.defaults.timezone'));
        $this->assertSame('hello@example.com', config('store.defaults.support_email'));
    }

    public function test_store_settings_database_conventions_are_defined(): void
    {
        $this->assertSame('store_settings', config('store.settings.table'));
        $this->assertSame('store.settings', config('store.settings.cache_key'));
        $this->assertSame(300, config('store.settings.cache_ttl'));
    }
}
