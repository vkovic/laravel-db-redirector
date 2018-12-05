<?php

namespace Vkovic\LaravelDbRedirector\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Vkovic\LaravelDbRedirector\DbRedirectorRouter;

class DbRedirectorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->app->bind(DbRedirectorRouter::class, function () {
            $router = new Router($this->app['events']);

            return new DbRedirectorRouter($router);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}