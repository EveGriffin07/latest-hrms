<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // === ADD THIS LINE TO FIX NGROK CSS ===
        if(env('APP_ENV') !== 'local' || request()->isSecure() || str_contains(request()->getHost(), 'ngrok')) {
            URL::forceScheme('https');
        }
    }
}
