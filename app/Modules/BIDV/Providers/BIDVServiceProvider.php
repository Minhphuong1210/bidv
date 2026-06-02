<?php

namespace App\Modules\BIDV\Providers;

use Illuminate\Support\ServiceProvider;

class BIDVServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__.'/../config/bidv.php', 'bidv'
        );

        // Bind Services
        $this->app->singleton(\App\Modules\BIDV\Services\BIDVClientService::class, function ($app) {
            return new \App\Modules\BIDV\Services\BIDVClientService();
        });

        $this->app->singleton(\App\Modules\BIDV\Services\BIDVServerService::class, function ($app) {
            return new \App\Modules\BIDV\Services\BIDVServerService();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../Views', 'BIDV');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/bidv.php' => config_path('bidv.php'),
        ], 'bidv-config');
    }
}
