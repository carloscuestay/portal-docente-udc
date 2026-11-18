<?php

namespace App\Providers;

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
        // Ajustes guardados desde la interfaz del docente (registro abierto o por dominio institucional)
        \App\Support\PortalSettings::apply();
    }
}
