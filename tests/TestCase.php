<?php

namespace Vkovic\LaravelDbRedirector\Test;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Vkovic\LaravelDbRedirector\Http\Middleware\DbRedirectorMiddleware;
use Vkovic\LaravelDbRedirector\Providers\DbRedirectorServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Route;

class TestCase extends OrchestraTestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->packageMigrations();
    }

    /**
     * Run default package migrations
     *
     * @return void
     */
    protected function packageMigrations()
    {
        $this->artisan('migrate');
    }

    /**
     * Get package providers
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [DbRedirectorServiceProvider::class];
    }

    /**
     * Define environment setup
     *
     * @param Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $app->make(Kernel::class)->pushMiddleware(DbRedirectorMiddleware::class);
    }
}