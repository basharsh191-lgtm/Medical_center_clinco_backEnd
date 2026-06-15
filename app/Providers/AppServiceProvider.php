<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force generated URLs (including Storage::url() and asset())
        // to use https in production so we don't ship http:// links the
        // browser then upgrades / blocks as mixed content.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
