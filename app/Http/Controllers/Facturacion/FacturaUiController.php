<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\FacturaBorrador;
use Illuminate\Support\Facades\Log;

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

    /**
     * Intenta decodificar el payload desde el request, tolerando que venga con comillas HTML (&quot;).
     * @return array payload como arreglo asociativo; si falla, regresa [].
     */
    private function decodePayloadFromRequest(Request $r): array
    {
        $raw = $r->input('payload', '');

        // 1) Intenta decodificar tal cual
        $payload = json_decode($raw, true);

        // 2) Si falló, intenta des-escapar entidades HTML (&quot;) y reintentar
        if (!is_array($payload)) {
            $raw2 = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
            $payload = json_decode($raw2, true);
        }

        // 3) Garantiza arreglo
        return is_array($payload) ? $payload : [];
    }


    public function create(Request $request)
    {
        $prefill = session()->pull('factura_prefill');
        $rfcActivo = session('rfc_seleccionado');
        $rfcUsuarioId = (int) session('rfc_usuario_id');

        if (!$rfcUsuarioId && $rfcActivo) {
            $rfcUsuarioId = (int) DB::table('rfc_usuarios')->where('rfc', $rfcActivo)->value('id');
        }

        // =======================
        // Clientes (ajusta where si ligas por rfc_usuario_id)
        // =======================
        $clientes = DB::table('clientes')
            // ->where('rfc_usuario_id', $rfcUsuarioId) // ← descomenta si aplica
            ->orderBy('razon_social')
            ->get([
                'id','rfc','razon_social','email',
                'calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais'
            ]);

        // =======================
        // SAT: Método y Forma de pago
        // Si no existen las tablas, usamos FALLBACK arrays
        // =======================

        // Fallbacks compactos y prácticos (puedes ampliar después):
        $fallbackFormasPago = collect([
            ['clave'=>'01','descripcion'=>'Efectivo'],
            ['clave'=>'02','descripcion'=>'Cheque nominativo'],
            ['clave'=>'03','descripcion'=>'Transferencia electrónica de fondos'],
            ['clave'=>'04','descripcion'=>'Tarjeta de crédito'],
            ['clave'=>'28','descripcion'=>'Tarjeta de débito'],
            ['clave'=>'29','descripcion'=>'Tarjeta de servicios'],
            ['clave'=>'99','descripcion'=>'Por definir'],
        ]);

        $fallbackMetodosPago = collect([
            ['clave'=>'PUE','descripcion'=>'Pago en una sola exhibición'],
            ['clave'=>'PPD','descripcion'=>'Pago en parcialidades o diferido'],
        ]);

        // Intentar leer de BD si existen tablas
        if (Schema::hasTable('sat_forma_pago')) {
            $formasPago = DB::table('sat_forma_pago')
                ->orderBy('clave')
                ->get(['clave','descripcion']);
            if ($formasPago->isEmpty()) $formasPago = $fallbackFormasPago;
        } elseif (Schema::hasTable('c_forma_pago')) {
            // Alterno si tu tabla se llama distinto (c_forma_pago)
            $formasPago = DB::table('c_forma_pago')
                ->orderBy('Clave')
                ->get()
                ->map(fn($r)=>['clave'=>$r->Clave,'descripcion'=>$r->Descripcion]);
            if ($formasPago->isEmpty()) $formasPago = $fallbackFormasPago;
        } else {
            $formasPago = $fallbackFormasPago;
        }

        if (Schema::hasTable('sat_metodo_pago')) {
            $metodosPago = DB::table('sat_metodo_pago')
                ->whereIn('clave', ['PUE','PPD'])
                ->orderBy('clave')
                ->get(['clave','descripcion']);
            if ($metodosPago->isEmpty()) $metodosPago = $fallbackMetodosPago;
        } elseif (Schema::hasTable('c_metodo_pago')) {
            $metodosPago = DB::table('c_metodo_pago')
                ->whereIn('Clave', ['PUE','PPD'])
                ->orderBy('Clave')
                ->get()
                ->map(fn($r)=>['clave'=>$r->Clave,'descripcion'=>$r->Descripcion]);
            if ($metodosPago->isEmpty()) $metodosPago = $fallbackMetodosPago;
        } else {
            $metodosPago = $fallbackMetodosPago;
        }

        // =======================
        // Ventana de fecha (72h)
        // =======================
        $minFecha = now()->copy()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha = now()->format('Y-m-d\TH:i');

        return view('facturacion.facturas.create', [
            'rfcActivo'     => $rfcActivo,
            'rfcUsuarioId'  => $rfcUsuarioId,
            'clientes'      => $clientes,
            'formasPago'    => $formasPago,
            'metodosPago'   => $metodosPago,
            'minFecha'      => $minFecha,
            'maxFecha'      => $maxFecha,
            'prefill' => $prefill,
        ]);
    }


    /**
     * Devuelve la siguiente Serie/Folio para el RFC activo y tipo (I/E).
     * GET /api/series/next?tipo=I|E
     */
    public function apiSeriesNext(\Illuminate\Http\Request $r)
    {
        $tipoUi = strtoupper($r->query('tipo','I'));
        if (!in_array($tipoUi, ['I','E'])) $tipoUi = 'I';

        // Mapeo a tu BD
        $tipoBd = $tipoUi === 'I' ? 'Ingreso' : 'Egreso';

        $rfcUsuarioId = (int) session('rfc_usuario_id');

        $q = \DB::table('folios')->where('tipo', $tipoBd);
        if ($rfcUsuarioId) {
            $q->where('rfc_usuario_id', $rfcUsuarioId);
        }

        $folioRow = $q->orderByDesc('id')->first();

        if (!$folioRow) {
            return response()->json([
                'serie'     => '',
                'siguiente' => 1,
                'folio'     => 1,
                'tipo'      => $tipoUi,
            ]);
        }

        $serie = (string) ($folioRow->serie ?? '');
        $sig   = (int) ($folioRow->folio ?? 0) + 1;

        return response()->json([
            'serie'     => $serie,
            'siguiente' => $sig,
            'folio'     => $sig,
            'tipo'      => $tipoUi,
        ]);
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

        session()->put('factura_preview_payload', $payload);

        return view('facturacion.facturas.preview', [
            'emisor_rfc'  => session('rfc_seleccionado'),
            'comprobante' => $payload,
            'cliente'     => $cliente,
            'totales'     => compact('subtotal','descuento','impuestos','total'),
        ]);
    }

    public function loadIntoCreate(\App\Models\FacturaBorrador $borrador)
    {
        $payload = $borrador->payload ?? [];
        if (!isset($payload['cliente_id']) && $borrador->cliente_id) {
            $payload['cliente_id'] = (int) $borrador->cliente_id;
        }
        session(['factura_restore_payload' => $payload]); // <<-- esta clave es la que consume create.blade.php

        return redirect()->route('facturas.create')
                        ->with('ok', 'Borrador #'.$borrador->id.' cargado en creación.');
    }


    public function prefillFromPreview(\Illuminate\Http\Request $r)
    {
        $payload = json_decode($r->input('payload','{}'), true) ?: [];
        // ✅ Usa la MISMA clave que lee create.blade.php
        session(['factura_restore_payload' => $payload]);
        return redirect()->route('facturas.create');
    }



    /**
     * Guardar (borrador) — alias
     * POST /facturacion/facturas  y /facturacion/facturas/guardar
     */

    public function store(Request $r)
    {
        Log::info('[facturas.guardar] HIT', [
            'method' => $r->method(),
            'url'    => $r->fullUrl(),
            'route'  => optional($r->route())->getName(),
        ]);

        return $this->guardar($r);
    }



    public function guardar(Request $r)
    {
        // 1) Decodificar payload de forma robusta
        $payload = $this->decodePayloadFromRequest($r);

        if (empty($payload) || !isset($payload['cliente_id']) || !isset($payload['conceptos']) || !is_array($payload['conceptos'])) {
            Log::warning('[facturas.guardar] Payload inválido o incompleto', [
                'raw_len' => strlen($r->input('payload', '')),
            ]);
            return back()->with('error', 'No fue posible leer el contenido a guardar (payload inválido).');
        }

        // 2) Recalcular totales por seguridad
        $subtotal  = 0.0;
        $descuento = 0.0;
        $impuestos = 0.0;

        foreach ($payload['conceptos'] as $c) {
            $cantidad = (float)($c['cantidad'] ?? 0);
            $precio   = (float)($c['precio'] ?? 0);
            $des      = (float)($c['descuento'] ?? 0);

            $sub  = $cantidad * $precio;
            $base = max($sub - $des, 0);

            $subtotal  += $sub;
            $descuento += $des;

            // Impuestos por concepto (si es que vienen)
            if (!empty($c['impuestos']) && is_array($c['impuestos'])) {
                foreach ($c['impuestos'] as $i) {
                    $factor = $i['factor'] ?? '';
                    if ($factor === 'Exento') {
                        continue;
                    }
                    $tasaPct = (float)($i['tasa'] ?? 0);
                    $monto   = $base * ($tasaPct / 100.0);

                    $tipoMov = $i['tipo'] ?? 'T'; // T=Traslado, R=Retención
                    $impuestos += ($tipoMov === 'R') ? -$monto : $monto;
                }
            }
        }

        $total = $subtotal - $descuento + $impuestos;

        // 3) Persistir en factura_borradores
        $b = new FacturaBorrador();
        $b->user_id        = auth()->id();
        $b->rfc_usuario_id = (int) session('rfc_usuario_id'); // si tu DB permite null, úsalo en migración/casts
        $b->cliente_id     = (int) $payload['cliente_id'];

        $b->tipo           = $payload['tipo_comprobante'] ?? 'I';
        $b->serie          = $payload['serie'] ?? null;
        $b->folio          = (string)($payload['folio'] ?? '');
        $b->fecha          = $payload['fecha'] ?? now();

        $b->metodo_pago    = $payload['metodo_pago'] ?? 'PUE';
        $b->forma_pago     = $payload['forma_pago'] ?? '99';
        $b->comentarios_pdf= $payload['comentarios_pdf'] ?? null;

        $b->subtotal       = round($subtotal, 2);
        $b->descuento      = round($descuento, 2);
        $b->impuestos      = round($impuestos, 2);
        $b->total          = round($total, 2);

        // MUY IMPORTANTE: que en el Modelo FacturaBorrador tengas `protected $casts = ['payload'=>'array'];`
        $b->payload        = $payload;
        $b->estatus        = 'borrador';
        $b->save();

        Log::info('[facturas.guardar] Borrador guardado', ['id' => $b->id]);

        // 4) Redirigir a una ruta GET existente para evitar 404 (GET /preview no existe)
        return redirect()
            ->route('facturas.borradores.index')
            ->with('ok', 'Borrador guardado (#' . $b->id . ').');
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
