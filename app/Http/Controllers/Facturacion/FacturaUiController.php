<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class FacturaUiController extends Controller
{
    /**
     * Devuelve la siguiente Serie/Folio para el RFC y tipo (I/E).
     * GET /api/series/next?tipo=I|E&rfc={rfc_usuario_id}
     */
    public function apiSeriesNext(Request $r)
    {
      $tipo = strtoupper($r->query('tipo','I'));
      if (!in_array($tipo, ['I','E'])) return response()->json(['serie'=>'','folio'=>'']);

      $rfcId = (int) $r->query('rfc', 0);
      if ($rfcId <= 0) $rfcId = (int) session('rfc_usuario_id');

      // folios: id, rfc_usuario_id, tipo, serie, folio_inicio, folio_actual, folio_fin, activo
      $folio = DB::table('folios')
        ->where('rfc_usuario_id', $rfcId)
        ->where('tipo', $tipo)
        ->where(function($q){ $q->whereNull('activo')->orWhere('activo',1); })
        ->orderByDesc('id')
        ->first();

      if (!$folio) return response()->json(['serie'=>'','folio'=>'']);

      $serie = (string)($folio->serie ?? '');
      $next  = (int)   ($folio->folio_actual ?? 0) + 1;
      // respetar límites si existen
      $ini   = (int)   ($folio->folio_inicio ?? 1);
      $fin   = (int)   ($folio->folio_fin ?? 0);
      if ($next < $ini) $next = $ini;
      if ($fin > 0 && $next > $fin) $next = $fin; // tope visual (persistencia real al timbrar)

      return response()->json(['serie'=>$serie, 'folio'=>$next]);
    }

    /**
     * Busca productos del RFC por código/descripcion.
     * GET /api/productos/buscar?q=...&rfc={rfc_usuario_id}
     */
    public function apiProductosBuscar(Request $r)
    {
      $q = trim($r->query('q',''));
      $rfcId = (int) $r->query('rfc', 0);
      if ($rfcId <= 0) $rfcId = (int) session('rfc_usuario_id');

      if ($q === '' || mb_strlen($q) < 2) return response()->json([]);

      $rows = DB::table('productos as p')
        ->leftJoin('sat_clave_prodserv as cps', 'cps.id', '=', 'p.clave_prodserv_id')
        ->leftJoin('sat_clave_unidad as cu', 'cu.id', '=', 'p.clave_unidad_id')
        ->where('p.rfc_usuario_id', $rfcId)
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
          DB::raw('COALESCE(cps.clave, p.clave_prodserv) as clave_prod_serv'),
          DB::raw('COALESCE(cu.clave, p.clave_unidad) as clave_unidad'),
          DB::raw('COALESCE(p.unidad, cu.nombre) as unidad'),
        ]);

      // Normaliza salida
      $out = $rows->map(function($x){
        return [
          'id' => (int)$x->id,
          'clave' => $x->clave,
          'descripcion' => $x->descripcion,
          'precio' => (float)($x->precio ?? 0),
          'clave_prod_serv' => (string)($x->clave_prod_serv ?? ''),
          'clave_unidad' => (string)($x->clave_unidad ?? ''),
          'unidad' => (string)($x->unidad ?? ''),
        ];
      })->values();

      return response()->json($out);
    }

    /**
     * Búsqueda ligera SAT: ClaveProdServ
     * GET /api/sat/clave-prod-serv?q=...
     */
    public function apiSatClaveProdServ(Request $r)
    {
      $q = trim($r->query('q',''));
      if (mb_strlen($q) < 3) return response()->json([]);
      $rows = DB::table('sat_clave_prodserv')
        ->where(function($w) use ($q){
          $w->where('clave','like',"%{$q}%")->orWhere('descripcion','like',"%{$q}%");
        })
        ->orderBy('clave')->limit(40)->get(['id','clave','descripcion']);
      return response()->json($rows->map(function($x){ return ['id'=>$x->id, 'clave'=>$x->clave, 'descripcion'=>$x->descripcion]; }));
    }

    /**
     * Búsqueda ligera SAT: ClaveUnidad
     * GET /api/sat/clave-unidad?q=...
     */
    public function apiSatClaveUnidad(Request $r)
    {
      $q = trim($r->query('q',''));
      if (mb_strlen($q) < 2) return response()->json([]);
      $rows = DB::table('sat_clave_unidad')
        ->where(function($w) use ($q){
          $w->where('clave','like',"%{$q}%")->orWhere('nombre','like',"%{$q}%");
        })
        ->orderBy('clave')->limit(40)->get(['id','clave', 'nombre as descripcion', 'nombre as unidad']);
      return response()->json($rows->map(function($x){ return ['id'=>$x->id, 'clave'=>$x->clave, 'descripcion'=>$x->descripcion, 'unidad'=>$x->unidad]; }));
    }

    /**
     * PREVIEW: valida payload y despliega invoice con botones Guardar/Timbrar
     * POST /facturacion/facturas/preview
     */
    public function preview(Request $request)
    {
      // el front manda todo en payload JSON
      $payload = json_decode($request->input('payload','{}'), true) ?: [];

      // validación fuerte
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
        'conceptos.*.clave_prod_serv' => 'required|string|max:8',
        'conceptos.*.clave_unidad'    => 'required|string|max:5',
        'conceptos.*.unidad'          => 'nullable|string|max:15',
        'conceptos.*.cantidad'        => 'required|numeric|min:0.001',
        'conceptos.*.precio'          => 'required|numeric|min:0',
        'conceptos.*.descuento'       => 'nullable|numeric|min:0',
      ])->validate();

      // prepara datos de cliente
      $cliente = DB::table('clientes')->where('id', $payload['cliente_id'])->first();
      abort_unless($cliente, 404);

      // totales (servidor)
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
          $impuestos += ($i['tipo'] ?? 'T') === 'R' ? -$m : $m;
        }
      }
      $total = $subtotal - $descuento + $impuestos;

      $data = [
        'emisor_rfc' => session('rfc_seleccionado'),
        'comprobante' => $payload,
        'cliente' => $cliente,
        'totales' => compact('subtotal','descuento','impuestos','total'),
      ];

      return view('facturacion.facturas.preview', $data);
    }

    /**
     * Guardar borrador desde preview (placeholder de persistencia)
     * POST /facturacion/facturas/guardar
     */
    public function guardar(Request $r)
    {
      // aquí eventualmente insertas a tus tablas de prefactura, etc.
      return back()->with('ok', 'Prefactura guardada (placeholder).');
    }

    /**
     * Timbrar desde preview (placeholder)
     * POST /facturacion/facturas/timbrar
     */
    public function timbrar(Request $r)
    {
      // aquí armarias el XML y timbras con el PAC
      return back()->with('ok', 'Timbrado enviado (placeholder).');
    }
}
