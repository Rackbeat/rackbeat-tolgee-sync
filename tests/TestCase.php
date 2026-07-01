<?php

namespace Rackbeat\TolgeeSync\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rackbeat\TolgeeSync\TolgeeSyncServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TolgeeSyncServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration
        config()->set('rackbeat-tolgee-sync.base_url', 'test-url');
        config()->set('rackbeat-tolgee-sync.base_project', 1);
        config()->set('rackbeat-tolgee-sync.sync_state', 'REVIEWED');
        config()->set('rackbeat-tolgee-sync.api_key', 'test-api-key');
        config()->set('rackbeat-tolgee-sync.namespace_prefix', 'test_');
        config()->set('rackbeat-tolgee-sync.master_locale', 'en');
        config()->set('rackbeat-tolgee-sync.master_folder', 'resources/lang');
        config()->set('rackbeat-tolgee-sync.project_locales', '["en","da","sv","fi","no"]');
        config()->set('rackbeat-tolgee-sync.skip_namespaces', []);
    }
}

