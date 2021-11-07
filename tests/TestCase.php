<?php

namespace RenokiCo\Thunder\Test;

use Orchestra\Testbench\TestCase as Orchestra;
use RenokiCo\Thunder\Test\Models\User;
use RenokiCo\Thunder\Thunder;

abstract class TestCase extends Orchestra
{
    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        Thunder::clearPlans();
        Thunder::cleanSyncUsageCallbacks();

        $this->resetDatabase();
        $this->loadLaravelMigrations(['--database' => 'sqlite']);
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->withFactories(__DIR__.'/database/factories');

        $this->artisan('migrate');
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app)
    {
        $cashierProviders = [
            \Laravel\Cashier\CashierServiceProvider::class,
            \Laravel\Paddle\CashierServiceProvider::class,
        ];

        $providers = [];

        foreach ($cashierProviders as $cashierProvider) {
            if (class_exists($cashierProvider)) {
                $providers[] = $cashierProvider;
            }
        }

        return array_merge($providers, [
            \Spark\SparkServiceProvider::class,
            \RenokiCo\Thunder\ThunderServiceProvider::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'wslxrEFGWY6GfGhvN9L3wH3KSRJQQpBD');
        $app['config']->set('auth.providers.users.model', Models\User::class);
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => __DIR__.'/database.sqlite',
            'prefix'   => '',
        ]);
        $app['config']->set('spark.billables.user.model', User::class);
        $app['config']->set('spark.billables.user.plans', []);
    }

    /**
     * Reset the database.
     *
     * @return void
     */
    protected function resetDatabase()
    {
        file_put_contents(__DIR__.'/database.sqlite', null);
    }
}
