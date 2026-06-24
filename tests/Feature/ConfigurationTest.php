<?php

namespace Rackbeat\TolgeeSync\Tests\Feature;

use Rackbeat\TolgeeSync\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_loads_configuration_correctly(): void
    {
        $this->assertEquals('https://translations.rkbt.io/v2/projects', config('rackbeat-tolgee-sync.base_url'));
        $this->assertEquals(1, config('rackbeat-tolgee-sync.base_project'));
        $this->assertEquals('REVIEWED', config('rackbeat-tolgee-sync.sync_state'));
        $this->assertEquals('test-api-key', config('rackbeat-tolgee-sync.api_key'));
        $this->assertEquals('test_', config('rackbeat-tolgee-sync.namespace_prefix'));
        $this->assertEquals('en', config('rackbeat-tolgee-sync.master_locale'));
    }

    /** @test */
    public function it_has_project_locales_configured(): void
    {
        $locales = config('rackbeat-tolgee-sync.project_locales');

        $this->assertIsArray($locales);
        $this->assertArrayHasKey(1, $locales);
        $this->assertContains('en', $locales[1]);
        $this->assertContains('no', $locales[1]);
        $this->assertContains('da', $locales[1]);
        $this->assertContains('sv', $locales[1]);
    }

    /** @test */
    public function it_can_publish_configuration(): void
    {
        $paths = \Illuminate\Support\ServiceProvider::pathsToPublish(
            \Rackbeat\TolgeeSync\TolgeeSyncServiceProvider::class);
        $this->assertNotEmpty($paths);
        $this->assertContains(config_path('rackbeat-tolgee-sync.php'), array_values($paths));
    }
}

