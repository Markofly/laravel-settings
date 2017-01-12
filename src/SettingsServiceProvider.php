<?php

namespace Markofly\Settings;

use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__.'/../config/settings.php' => config_path('markofly/settings.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations')
            ], 'migrations');

        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/settings.php', 'markofly.settings'
        );

        $this->app->bind('settings', function($app)
        {
            $config = $app->config->get('markofly.settings', [
                'settings_table_name' => 'markofly_settings'
            ]);
            return new Settings($app['db'], $config);
        });

    }
}
