<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SincronizarRfcSesion
{
    public function handle(Request $request, Closure $next)
    {
        // Si no hay ninguna llave, dejamos que otro middleware la establezca (p.ej. AsegurarRfcSeleccionado)
        $idNuevo = session('rfc_activo_id');
        $idLegado = session('rfc_seleccionado_id');
        $rfcTexto = session('rfc_seleccionado');
        $razon = session('rfc_razon_social');

        // Si solo existe el legado, crea el nuevo
        if (!$idNuevo && $idLegado) {
            session(['rfc_activo_id' => (int) $idLegado]);
            $idNuevo = $idLegado;
        }

        // Si existe el nuevo pero faltan los de display (texto), complétalos
        if ($idNuevo && (!$rfcTexto || !$razon)) {
            if (Schema::hasTable('rfc_usuarios')) {
                $fila = DB::table('rfc_usuarios')->where('id', $idNuevo)->first();
                if ($fila) {
                    session([
                        'rfc_seleccionado_id' => (int) $fila->id,
                        'rfc_seleccionado'    => (string) ($fila->rfc ?? ''),
                        'rfc_razon_social'    => (string) ($fila->razon_social ?? ''),
                    ]);
                }
            }
        }

        return $next($request);
    }
}
