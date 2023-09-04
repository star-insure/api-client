<?php

namespace StarInsure\Api\Providers;

use Illuminate\Support\ServiceProvider;
use StarInsure\Api\StarAuthManager;

class StarServiceProvider extends ServiceProvider
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
                // Publish config
                __DIR__.'/../../config/star.php' => config_path('star.php'),
                // Publish routes to the application so sessions can be used
                __DIR__.'/../../routes/oauth.php' => base_path('routes/oauth.php'),
            ], 'starinsure');
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
        $this->mergeConfigFrom(__DIR__.'/../../config/starConfig.php', 'star');

        // Register the main class to use with the facade
        $this->app->bind('starapi', function () {
            return new \StarInsure\Api\StarApi(
                config('star.auth_strategy'),
                config('star.version')
            );
        });

        // Register the auth manager
        $this->app->singleton('auth', function ($app) {
            return new StarAuthManager($app);
        });
    }
}
