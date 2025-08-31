<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AsegurarRfcSeleccionado
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, \Closure $next)
    {
        if (auth()->check() && !session()->has('rfc_seleccionado')) {
            $primerRfc = auth()->user()->rfcs()->first();
            if ($primerRfc) {
                session(['rfc_seleccionado' => $primerRfc->rfc]);
            }
        }
        return $next($request);
    }

}
