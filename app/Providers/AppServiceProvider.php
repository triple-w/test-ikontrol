<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Services\Timbres\TimbreService;
use App\Models\RfcUsuario;

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
    public function boot()
    {
        View::composer('*', function ($view) {
            try {
                if (Auth::check() && session('rfc_seleccionado')) {
                    $rfc = RfcUsuario::where('rfc', session('rfc_seleccionado'))
                        ->where('user_id', Auth::id())
                        ->first();

                    if ($rfc) {
                        $disp = app(TimbreService::class)->disponibles($rfc->id);
                        if ($disp <= 10) {
                            $view->with('banner_timbres', $disp);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // silencio para no romper vistas si hay error
            }
        });
    }
}
