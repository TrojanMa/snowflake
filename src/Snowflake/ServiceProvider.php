<?php

namespace LucasVscn\Snowflake;

use Illuminate\Support\ServiceProvider as Provider;

class ServiceProvider extends Provider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/snowflake.php', 'snowflake'
        );

        $this->app->singleton('LucasVscn\Snowflake\Client', function ($app)
        {
            return new Client(config('snowflake.host'), config('snowflake.port'));
        });
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/snowflake.php' => config_path('snowflake.php'),
        ]);
    }
}