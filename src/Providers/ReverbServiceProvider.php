<?php

namespace ZeeshanSaab\ReverbApi\Providers;

use Illuminate\Support\ServiceProvider;
use ZeeshanSaab\ReverbApi\ReverbApi;

class ReverbServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/reverb.php', 'reverb');

        $this->app->singleton('reverb.api', function ($app) {
            return new ReverbApi(config('reverb'));
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/reverb.php' => config_path('reverb.php'),
        ], 'config');
    }
}
