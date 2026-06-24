<?php

namespace Rackbeat\TolgeeSync;

use Illuminate\Support\ServiceProvider;
use Rackbeat\TolgeeSync\Commands\TolgeeInstall;
use Rackbeat\TolgeeSync\Commands\TolgeeSync;
class TolgeeSyncServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/rackbeat-tolgee-sync.php', 'rackbeat-tolgee-sync'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Register commands
            $this->commands([
                TolgeeSync::class,
                TolgeeInstall::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__ . '/config/rackbeat-tolgee-sync.php' => config_path('rackbeat-tolgee-sync.php'),
            ], 'rackeat-tolgee-sync-config');
        }
    }
}

