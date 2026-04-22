<?php

namespace ScienceWorks\HostConnection;

use Illuminate\Support\ServiceProvider;
use ScienceWorks\HostConnection\Client\HostConnectionClient;

class HostConnectionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/host-connection.php', 'host-connection');

        $this->app->singleton(HostConnectionClient::class, fn () => new HostConnectionClient());
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'host-connection');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/host-connection.php' => config_path('host-connection.php'),
            ], 'host-connection-config');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/host-connection'),
            ], 'host-connection-translations');
        }
    }
}
