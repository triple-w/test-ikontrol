<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

// Eloquent models
use App\Models\Folio;
use App\Models\Cliente;
use App\Models\RfcUsuario;

class FacturaUiController extends Controller
{
    /**
     * GET /facturacion/facturas/crear
     * Carga la vista de creación con datos que la Blade espera.
     */
    public function create(Request $request)
    {
        $rfcActivo = session('rfc_seleccionado');

        // ID del RFC de usuario activo
        $rfcUsuarioId = (int) session('rfc_usuario_id');
        if (!$rfcUsuarioId && $rfcActivo) {
            $rfcUsuarioId = (int) RfcUsuario::query()->where('rfc', $rfcActivo)->value('id');
        }

        // Clientes del RFC activo con campos que la vista serializa
        $clientes = Cliente::query()
            ->forActiveRfc()
            ->orderBy('razon_social')
            ->get([
                'id','rfc','razon_social','calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais','email',
            ]);

        // Ventana SAT: 72h hacia atrás hasta "ahora"
        $minFecha = now()->copy()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha = now()->format('Y-m-d\TH:i');

        return view('facturacion.facturas.create', compact(
            'rfcActivo','rfcUsuarioId','clientes','minFecha','maxFecha'
        ));
    }

    /**
     * Devuelve la siguiente Serie/Folio para el RFC activo y tipo (I/E).
     * GET /api/series/next?tipo=I|E
     */
    public function apiSeriesNext(Request $r)
    {
        $tipo = strtoupper($r->query('tipo','I'));
        if (!in_array($tipo, ['I','E'])) { $tipo = 'I'; }

        $folio = Folio::query()
            ->forActiveRfc()
            ->where('tipo', $tipo)
            ->orderByDesc('id')
            ->first();

        if (!$folio) {
            return response()->json(['serie'=>'', 'folio'=>1, 'tipo'=>$tipo]);
        }

        // Serie tolerante a distintos esquemas
        $serie = $folio->serie ?? ($folio->prefijo ?? '');

        // Siguiente número tolerante a distintos nombres de campo
        $next =
            ($folio->siguiente ?? null) ??
            ($folio->folio_siguiente ?? null) ??
            (isset($folio->folio_actual) ? ((int)$folio->folio_actual + 1) : null) ??
            (isset($folio->consecutivo) ? ((int)$folio->consecutivo + 1) : null) ??
            (isset($folio->folio) ? ((int)$folio->folio + 1) : null) ??
            1;

        return response()->json(['serie' => (string)$serie, 'folio' => (int)$next, 'tipo'=>$tipo]);
    }

    /**
     * Busca productos por código/descripcion con campos listos para la UI.
     * GET /api/productos/buscar?q=...
     */
    public function apiProductosBuscar(Request $r)
    {
        $q = trim($r->query('q',''));
        if ($q === '' || mb_strlen($q) < 2) return response()->json([]);

        // Determinar RFC usuario id (si lo pasas, úsalo; sino, por sesión)
        $rfcId = (int) $r->query('rfc', 0);
        if ($rfcId <= 0) {
            $rfcId = (int) session('rfc_usuario_id');
            if (!$rfcId && ($rfc = session('rfc_seleccionado'))) {
                $rfcId = (int) RfcUsuario::where('rfc', $rfc)->value('id');
            }
        }

        $rows = DB::table('productos as p')
            ->leftJoin('clave_prod_serv as cps', 'cps.id', '=', 'p.clave_prod_serv_id')
            ->leftJoin('clave_unidad as cu', 'cu.id', '=', 'p.clave_unidad_id')
            ->when($rfcId, fn($qq) => $qq->where('p.rfc_usuario_id', $rfcId))
            ->where(function($w) use ($q){
                $w->where('p.clave','like',"%{$q}%")
                  ->orWhere('p.descripcion','like',"%{$q}%");
            })
            ->orderBy('p.descripcion')
            ->limit(20)
            ->get([
                'p.id',
                'p.clave',
                'p.descripcion',
                'p.precio',
                DB::raw('COALESCE(cps.clave, "") as clave_prod_serv'),
                DB::raw('COALESCE(cu.clave,  "") as clave_unidad'),
                DB::raw('COALESCE(p.unidad, cu.descripcion) as unidad'),
            ]);

        return response()->json($rows);
    }

    /**
     * Autocompletar SAT Clave ProdServ.
     * GET /api/sat/clave-prod-serv?q=...
     */
    public function apiSatClaveProdServ(Request $r)
    {
        $q = trim($r->query('q',''));
        if (mb_strlen($q) < 2) return response()->json([]);
        $rows = DB::table('clave_prod_serv')
            ->where(function($w) use ($q){
                $w->where('clave','like',"%{$q}%")->orWhere('descripcion','like',"%{$q}%");
            })
            ->orderBy('clave')->limit(40)->get(['id','clave','descripcion']);
        return response()->json($rows);
    }

    /**
     * Autocompletar SAT Clave Unidad.
     * GET /api/sat/clave-unidad?q=...
     */
    public function apiSatClaveUnidad(Request $r)
    {
        $q = trim($r->query('q',''));
        if (mb_strlen($q) < 2) return response()->json([]);
        $rows = DB::table('clave_unidad')
            ->where(function($w) use ($q){
                $w->where('clave','like',"%{$q}%")->orWhere('descripcion','like',"%{$q}%");
            })
            ->orderBy('clave')->limit(40)->get(['id','clave','descripcion']);
        return response()->json($rows);
    }

    /**
     * PREVIEW: valida payload y despliega invoice con botones Guardar/Timbrar
     * POST /facturacion/facturas/preview
     */
    public function preview(Request $request)
    {
        $payload = json_decode($request->input('payload','{}'), true) ?: [];

        Validator::make($payload, [
            'tipo_comprobante'            => 'required|in:I,E',
            'serie'                       => 'required|string|max:10',
            'folio'                       => 'required',
            'fecha'                       => 'required|date',
            'metodo_pago'                 => 'required|in:PUE,PPD',
            'forma_pago'                  => 'required|string|max:3',
            'comentarios_pdf'             => 'nullable|string|max:2000',
            'cliente_id'                  => 'required|integer|min:1',
            'conceptos'                   => 'required|array|min:1',
            'conceptos.*.descripcion'     => 'required|string|max:1000',
            'conceptos.*.clave_prod_serv' => 'nullable|string|max:10',
            'conceptos.*.clave_unidad'    => 'nullable|string|max:10',
            'conceptos.*.unidad'          => 'nullable|string|max:50',
            'conceptos.*.cantidad'        => 'required|numeric|min:0.0001',
            'conceptos.*.precio'          => 'required|numeric|min:0',
            'conceptos.*.descuento'       => 'nullable|numeric|min:0',
        ])->validate();

        // Cliente (con fallback si tuvieras otra columna)
        $cliente = DB::table('clientes')->where('id', $payload['cliente_id'])->first();
        abort_unless($cliente, 404);
        if (!isset($cliente->razon_social) && isset($cliente->nombre)) {
            $cliente->razon_social = $cliente->nombre;
        }

        // Cálculo de totales del lado servidor (incluye retenciones/traslados)
        $subtotal=0; $descuento=0; $impuestos=0;
        foreach ($payload['conceptos'] as $c) {
            $sub = (float)$c['cantidad'] * (float)$c['precio'];
            $des = (float)($c['descuento'] ?? 0);
            $base = max($sub - $des, 0);
            $subtotal += $sub;
            $descuento += $des;
            foreach (($c['impuestos'] ?? []) as $i) {
                if (($i['factor'] ?? '') === 'Exento') continue;
                $tasa = (float)($i['tasa'] ?? 0) / 100;
                $m = $base * $tasa;
                $impuestos += (($i['tipo'] ?? 'T') === 'R') ? -$m : $m;
            }
        }
        $total = $subtotal - $descuento + $impuestos;

        return view('facturacion.facturas.preview', [
            'emisor_rfc'  => session('rfc_seleccionado'),
            'comprobante' => $payload,
            'cliente'     => $cliente,
            'totales'     => compact('subtotal','descuento','impuestos','total'),
        ]);
    }

    /**
     * Guardar (borrador) — alias
     * POST /facturacion/facturas  y /facturacion/facturas/guardar
     */
    public function store(Request $r) { return $this->guardar($r); }

    public function guardar(Request $r)
    {
        // Persistencia real pendiente según tu modelo/tablas
        return back()->with('ok', 'Prefactura guardada (placeholder).');
    }

    /**
     * Timbrar desde preview (placeholder)
     * POST /facturacion/facturas/timbrar
     */
    public function timbrar(Request $r)
    {
        // Aquí armarías el XML y timbras con el PAC
        return back()->with('ok', 'Timbrado enviado (placeholder).');
    }
}
