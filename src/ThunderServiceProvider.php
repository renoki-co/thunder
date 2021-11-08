<?php

namespace RenokiCo\Thunder;

use Illuminate\Support\ServiceProvider;

class ThunderServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('thunder.manager', ThunderManager::class);
    }
}
