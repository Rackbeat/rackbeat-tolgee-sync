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
        config()->set('tolgee.base_url', 'https://translations.rkbt.io/v2/projects');
        config()->set('tolgee.base_project', 1);
        config()->set('tolgee.sync_state', 'REVIEWED');
        config()->set('tolgee.api_key', 'test-api-key');
        config()->set('tolgee.namespace_prefix', 'test_');
        config()->set('tolgee.master_locale', 'en');
        config()->set('tolgee.master_folder', 'resources/lang');
        config()->set('tolgee.project_locales', [
            1 => ['en', 'no', 'da', 'sv'],
        ]);
        config()->set('tolgee.skip_namespaces', []);
    }
}

