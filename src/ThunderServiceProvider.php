<?php

namespace RenokiCo\Thunder;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier as StripeCashier;
use Laravel\Paddle\Cashier as PaddleCashier;
use RenokiCo\Thunder\Models\PaddleSubscription;
use RenokiCo\Thunder\Models\StripeSubscription;

class ThunderServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'migrations');
        }

        if (class_exists(StripeCashier::class)) {
            StripeCashier::useSubscriptionModel(config('spark.subscription.model', StripeSubscription::class));
        }

        if (class_exists(PaddleCashier::class)) {
            PaddleCashier::useSubscriptionModel(config('spark.subscription.model', PaddleSubscription::class));
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('spark.manager', ThunderManager::class);
    }
}
