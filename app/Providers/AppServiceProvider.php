<?php

namespace App\Providers;

use App\Services\InventoryAlertService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function ($view): void {
            $usuario = Auth::user();
            $alertasNuevas = $usuario
                ? app(InventoryAlertService::class)->syncStore((int) $usuario->id_tienda)
                : 0;

            $view->with('alertasNuevas', $alertasNuevas);
        });
    }
}
