<?php

namespace KeivnYan\Elastic\Providers;

use Illuminate\Support\ServiceProvider;
use KevinYan\Elastic\ElasticManager;

class ElasticServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('elastic.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('elastic', function ($app) {
            $app->make(ElasticManager::class);
        });
    }
}
