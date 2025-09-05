<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class FacturaUiController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();
        $rfcUsuarioId = (int) session('rfc_activo_id');

        // Ventana de 72h
        $now = Carbon::now();
        $minFecha = $now->copy()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha = $now->copy()->format('Y-m-d\TH:i');

        // Clientes visibles en el select (lo que pediste)
        $clientes = DB::table('clientes')
            ->when($rfcUsuarioId, fn($q) => $q->where('rfc_usuario_id', $rfcUsuarioId))
            ->orderBy('razon_social')
            ->get([
                'id',
                'rfc',
                'razon_social',
                'calle', 'no_ext', 'no_int', 'colonia', 'localidad',
                'estado', 'codigo_postal', 'pais', 'email',
            ]);

        // Serie/Folio por default (Ingreso)
        $serieFolio = $this->obtenerSiguienteFolio($rfcUsuarioId, 'I');

        return view('facturacion.facturas.create', [
            'rfcUsuarioId' => $rfcUsuarioId,
            'clientes'     => $clientes,
            'minFecha'     => $minFecha,
            'maxFecha'     => $maxFecha,
            'defaultSerie' => $serieFolio['serie'] ?? '',
            'defaultFolio' => $serieFolio['folio'] ?? '',
        ]);
    }

    // GET /api/series/next?tipo=I|E&rfc_usuario_id=#
    public function nextFolio(Request $request)
    {
        $request->validate([
            'tipo'            => 'required|in:I,E',
            'rfc_usuario_id'  => 'required|integer',
        ]);
        $data = $this->obtenerSiguienteFolio((int)$request->rfc_usuario_id, $request->tipo);
        return response()->json($data);
    }

    private function obtenerSiguienteFolio(int $rfcUsuarioId, string $tipo): array
    {
        // Detecta columnas disponibles en 'folios'
        $hasRfcUsuarioId = Schema::hasColumn('folios', 'rfc_usuario_id');
        $hasRfcId        = Schema::hasColumn('folios', 'rfc_id');

        // Columnas posibles para "tipo"
        $posiblesTipo = [
            'tipo',                // I / E
            'tipo_cfdi',           // I / E
            'tipo_de_comprobante', // I / E
            'tipo_comprobante',    // I / E
            'comprobante',         // 'Ingreso' / 'Egreso'
            'clase',               // a veces guardan 'Ingreso'/'Egreso' aquí
        ];
        $colTipo = null;
        foreach ($posiblesTipo as $c) {
            if (Schema::hasColumn('folios', $c)) { $colTipo = $c; break; }
        }

        // Columnas posibles para serie y folio
        $posiblesSerie = ['serie','serie_factura','serie_ingreso','serie_egreso'];
        $colSerie = null;
        foreach ($posiblesSerie as $c) {
            if (Schema::hasColumn('folios', $c)) { $colSerie = $c; break; }
        }

        $posiblesFolioActual = ['folio_actual','folio','ultimo_folio','consecutivo'];
        $colFolioActual = null;
        foreach ($posiblesFolioActual as $c) {
            if (Schema::hasColumn('folios', $c)) { $colFolioActual = $c; break; }
        }

        $posiblesFolioFin = ['folio_fin','folio_hasta','hasta','max_folio'];
        $colFolioFin = null;
        foreach ($posiblesFolioFin as $c) {
            if (Schema::hasColumn('folios', $c)) { $colFolioFin = $c; break; }
        }

        // Construye la query sin referenciar columnas inexistentes
        $q = \DB::table('folios');

        // Scope por RFC activo (usa rfc_usuario_id si existe; si no, intenta rfc_id)
        if ($hasRfcUsuarioId) {
            $q->where('rfc_usuario_id', $rfcUsuarioId);
        } elseif ($hasRfcId) {
            $q->where('rfc_id', $rfcUsuarioId);
        }

        // Filtro por tipo si hay columna para ello
        if ($colTipo) {
            // Algunas tablas guardan 'Ingreso'/'Egreso' en lugar de 'I'/'E'
            $mapLargo = ['I' => 'Ingreso', 'E' => 'Egreso'];
            if (in_array($colTipo, ['comprobante','clase'])) {
                $q->where($colTipo, $mapLargo[$tipo] ?? $tipo);
            } else {
                $q->where($colTipo, $tipo);
            }
        }

        $row = $q->orderBy('id')->first();

        if (!$row) {
            // Sin configuración: regresa vacío para que la UI lo muestre claramente
            return ['serie' => '', 'folio' => ''];
        }

        $serie       = $colSerie       ? (string) ($row->{$colSerie} ?? '') : '';
        $folioActual = $colFolioActual ? (int) ($row->{$colFolioActual} ?? 0) : 0;
        $folioFin    = $colFolioFin    ? (int) ($row->{$colFolioFin} ?? 0)    : 0;

        $siguiente = $folioActual > 0 ? $folioActual + 1 : 1;
        if ($folioFin > 0) {
            $siguiente = min($siguiente, $folioFin);
        }

        return [
            'serie' => $serie,
            'folio' => (string) $siguiente,
        ];
    }

}
