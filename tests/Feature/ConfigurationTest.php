<?php

namespace Rackbeat\TolgeeSync\Tests\Feature;

use Rackbeat\TolgeeSync\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** @test */
    public function it_loads_configuration_correctly(): void
    {
        $this->assertEquals(1, config('rackbeat-tolgee-sync.base_project'));
        $this->assertEquals('REVIEWED', config('rackbeat-tolgee-sync.sync_state'));
        $this->assertEquals('test_', config('rackbeat-tolgee-sync.namespace_prefix'));
        $this->assertEquals('en', config('rackbeat-tolgee-sync.master_locale'));
    }

    /** @test */
    public function it_has_project_locales_configured(): void
    {
        $locales = config('rackbeat-tolgee-sync.project_locales');

        $this->assertEquals('["en","da","sv","fi","no"]', $locales);
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

