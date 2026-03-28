<?php

namespace App\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Windows dev: PHP cURL lacks a CA bundle, disable SSL verification locally.
        if ($this->app->isLocal()) {
            Http::globalOptions(['verify' => false]);
        }
    }
}
