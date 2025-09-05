<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
        // Ajusta a tu estructura real de tabla de folios
        // Campos esperados: rfc_usuario_id, tipo ('I'|'E'), serie, folio_actual, folio_fin (opcional)
        $row = DB::table('folios')
            ->where('rfc_usuario_id', $rfcUsuarioId)
            ->where(function ($q) use ($tipo) {
                // Algunas BD usan 'tipo' y otras 'tipo_comprobante'
                $q->where('tipo', $tipo)->orWhere('tipo_comprobante', $tipo);
            })
            ->orderBy('id')
            ->first();

        if (!$row) {
            return ['serie' => '', 'folio' => ''];
        }

        $folioActual = (int) ($row->folio_actual ?? 0);
        $siguiente = $folioActual > 0 ? ($folioActual + 1) : 1;

        // Si existe folio_fin, respeta el rango
        if (isset($row->folio_fin) && (int)$row->folio_fin > 0) {
            $siguiente = min($siguiente, (int)$row->folio_fin);
        }

        return [
            'serie' => (string)($row->serie ?? ''),
            'folio' => (string)$siguiente,
        ];
    }

    // GET /api/productos/buscar?q=...
    public function buscarProductos(Request $request)
    {
        $rfcUsuarioId = (int) $request->input('rfc_usuario_id');
        $q = trim((string) $request->input('q', ''));

        $productos = DB::table('productos')
            ->when($rfcUsuarioId, fn($qb) => $qb->where('rfc_usuario_id', $rfcUsuarioId))
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('descripcion', 'like', "%{$q}%")
                      ->orWhere('no_identificacion', 'like', "%{$q}%");
                });
            })
            ->orderBy('descripcion')
            ->limit(20)
            ->get([
                'id',
                'descripcion',
                'precio',
                'clave_prod_serv_id',
                'clave_unidad_id',
                'unidad',
            ]);

        return response()->json($productos);
    }

    // POST /facturacion/facturas/preview
    public function preview(Request $request)
    {
        // Valida lo mínimo para visualizar
        $validated = $request->validate([
            'encabezado'  => 'required|array',
            'cliente'     => 'required|array',
            'conceptos'   => 'required|array|min:1',
            'relaciones'  => 'array',
            'totales'     => 'required|array',
        ]);

        return view('facturacion.facturas.preview', $validated);
    }

    // POST /facturacion/facturas/guardar  (placeholder)
    public function store(Request $request)
    {
        // Aquí harás validaciones SAT, armado XML, timbrado, envío, timbres--
        return back()->with('status', 'Guardado (placeholder)');
    }
}
