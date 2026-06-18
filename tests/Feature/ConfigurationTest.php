<?php

namespace Rackbeat\TolgeeSync\Tests\Feature;

use Rackbeat\TolgeeSync\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_loads_configuration_correctly(): void
    {
        $this->assertEquals('https://translations.rkbt.io/v2/projects', config('tolgee.base_url'));
        $this->assertEquals(1, config('tolgee.base_project'));
        $this->assertEquals('REVIEWED', config('tolgee.sync_state'));
        $this->assertEquals('test-api-key', config('tolgee.api_key'));
        $this->assertEquals('test_', config('tolgee.namespace_prefix'));
        $this->assertEquals('en', config('tolgee.master_locale'));
    }

    /** @test */
    public function it_has_project_locales_configured(): void
    {
        $locales = config('tolgee.project_locales');

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
        $this->assertContains(config_path('tolgee.php'), array_values($paths));
    }
}

