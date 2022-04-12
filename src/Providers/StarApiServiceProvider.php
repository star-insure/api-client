<?php

namespace StarInsure\Api\Providers;

use Illuminate\Support\ServiceProvider;

class StarApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/apiConfig.php' => config_path('star-api.php'),
            ], 'config');
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../../config/apiConfig.php', 'star-api');

        // Register the main class to use with the facade
        $this->app->bind('starapi', function () {
            return new \StarInsure\Api\StarApi(
                config('star-api.auth_strategy'),
                config('star-api.version')
            );
        });
    }
}
