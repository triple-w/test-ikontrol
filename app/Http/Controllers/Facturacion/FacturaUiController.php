<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class FacturaUiController extends Controller
{
    public function create(Request $request)
    {
        $rfcActivo    = session('rfc_seleccionado');
        $rfcUsuarioId = (int) session('rfc_usuario_id');

        $clientes = DB::table('clientes')->orderBy('razon_social')->get([
            'id','rfc','razon_social','email','calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais'
        ]);

        // Métodos/Formas SAT con fallback (sin tocar tu BD si no existen)
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

        if (Schema::hasTable('sat_forma_pago')) {
            $formasPago = DB::table('sat_forma_pago')->orderBy('clave')->get(['clave','descripcion']);
            if ($formasPago->isEmpty()) $formasPago = $fallbackFormasPago;
        } elseif (Schema::hasTable('c_forma_pago')) {
            $formasPago = DB::table('c_forma_pago')->orderBy('Clave')->get()
                ->map(fn($r)=>['clave'=>$r->Clave,'descripcion'=>$r->Descripcion]);
            if ($formasPago->isEmpty()) $formasPago = $fallbackFormasPago;
        } else $formasPago = $fallbackFormasPago;

        if (Schema::hasTable('sat_metodo_pago')) {
            $metodosPago = DB::table('sat_metodo_pago')->whereIn('clave',['PUE','PPD'])->orderBy('clave')->get(['clave','descripcion']);
            if ($metodosPago->isEmpty()) $metodosPago = $fallbackMetodosPago;
        } elseif (Schema::hasTable('c_metodo_pago')) {
            $metodosPago = DB::table('c_metodo_pago')->whereIn('Clave',['PUE','PPD'])->orderBy('Clave')->get()
                ->map(fn($r)=>['clave'=>$r->Clave,'descripcion'=>$r->Descripcion]);
            if ($metodosPago->isEmpty()) $metodosPago = $fallbackMetodosPago;
        } else $metodosPago = $fallbackMetodosPago;

        $minFecha = now()->copy()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha = now()->format('Y-m-d\TH:i');

        return view('facturacion.facturas.create', [
<<<<<<< HEAD
            'rfcActivo'      => $rfcActivo,
            'rfcUsuarioId'   => $rfcUsuarioId,
            'clientes'       => $clientes,
            'formasPago'     => $formasPago,
            'metodosPago'    => $metodosPago,
            'minFecha'       => $minFecha,
            'maxFecha'       => $maxFecha,
            // defaults para includes
            'comentarios_pdf'=> '',
            'relacionados'   => [],
=======
            'rfcActivo'     => $rfcActivo,
            'rfcUsuarioId'  => $rfcUsuarioId,
            'clientes'      => $clientes,
            'formasPago'    => $formasPago,
            'metodosPago'   => $metodosPago,
            'minFecha'      => $minFecha,
            'maxFecha'      => $maxFecha,
>>>>>>> parent of c14a727 (cambios)
        ]);
    }

    // Serie/Folio: mapea I/E -> Ingreso/Egreso (tu tabla usa texto completo)
    public function apiSeriesNext(Request $r)
    {
        $tipoUi = strtoupper($r->query('tipo','I'));
        if (!in_array($tipoUi, ['I','E'])) $tipoUi = 'I';
        $tipoBd = $tipoUi === 'I' ? 'Ingreso' : 'Egreso';

        $rfcUsuarioId = (int) session('rfc_usuario_id');

        $q = DB::table('folios')->where('tipo', $tipoBd);
        if ($rfcUsuarioId) $q->where('rfc_usuario_id', $rfcUsuarioId);

        $row = $q->orderByDesc('id')->first();
        if (!$row) return response()->json(['serie'=>'', 'siguiente'=>1, 'folio'=>1, 'tipo'=>$tipoUi]);

        $serie = (string)($row->serie ?? '');
        $sig   = (int)($row->folio ?? 0) + 1;

        return response()->json(['serie'=>$serie, 'siguiente'=>$sig, 'folio'=>$sig, 'tipo'=>$tipoUi]);
    }

    // Productos para el select externo (tal cual ya usas)
    public function apiProductosBuscar(Request $r)
    {
        $q = trim($r->query('q',''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $rfcId = (int) session('rfc_usuario_id');

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
                'p.id','p.clave','p.descripcion','p.precio',
                DB::raw('COALESCE(cps.clave, "") as clave_prod_serv'),
                DB::raw('COALESCE(cu.clave,  "") as clave_unidad'),
                DB::raw('COALESCE(p.unidad, cu.descripcion) as unidad'),
            ]);

        return response()->json($rows);
    }

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

    public function preview(Request $request)
    {
        $payload = json_decode($request->input('payload','{}'), true) ?: [];

        Validator::make($payload, [
            'tipo_comprobante'            => 'required|in:I,E',
            'serie'                       => 'required|string|max:20',
            'folio'                       => 'required',
            'fecha'                       => 'required|date',
            'metodo_pago'                 => 'required|string|max:5',
            'forma_pago'                  => 'required|string|max:3',
            'cliente_id'                  => 'required|integer|min:1',
            'conceptos'                   => 'required|array|min:1',
            'conceptos.*.descripcion'     => 'required|string|max:1000',
            'conceptos.*.cantidad'        => 'required|numeric|min:0.0001',
            'conceptos.*.precio'          => 'required|numeric|min:0',
        ])->validate();

        $cliente = DB::table('clientes')->where('id', $payload['cliente_id'])->first();
        abort_unless($cliente, 404);

        $subtotal=0; $descuento=0; $impuestos=0;
        foreach ($payload['conceptos'] as $c) {
            $sub = (float)$c['cantidad'] * (float)$c['precio'];
            $des = (float)($c['descuento'] ?? 0);
            $base = max($sub - $des, 0);
            $subtotal += $sub;
            $descuento += $des;
            foreach (($c['impuestos'] ?? []) as $i) {
                if (strtolower($i['factor'] ?? '') === 'exento') continue;
                $tasa = (float)($i['tasa'] ?? 0) / 100;
                $m = $base * $tasa;
                $impuestos += (($i['tipo'] ?? 'T') === 'R') ? -$m : $m;
            }
        }
        $total = $subtotal - $descuento + $impuestos;

        $comentarios_pdf = $this->extraerComentarios($payload);
        $relacionados    = $this->normalizarRelacionados($payload);

        return view('facturacion.facturas.preview', [
            'emisor_rfc'      => session('rfc_seleccionado'),
            'comprobante'     => $payload,
            'cliente'         => $cliente,
            'totales'         => compact('subtotal','descuento','impuestos','total'),
            'comentarios_pdf' => $comentarios_pdf,
            'relacionados'    => $relacionados,
        ]);
    }

    public function store(Request $r) { return $this->guardar($r); }

    public function guardar(Request $r)
    {
        $p = json_decode($r->input('payload','{}'), true) ?: [];
        if (!isset($p['cliente_id']) || !isset($p['conceptos'])) {
            return back()->with('error','Payload incompleto.');
        }

        $subtotal=0; $descuento=0; $impuestos=0;
        foreach ($p['conceptos'] as $c) {
            $sub = (float)$c['cantidad'] * (float)$c['precio'];
            $des = (float)($c['descuento'] ?? 0);
            $base = max($sub - $des, 0);
            $subtotal += $sub;
            $descuento += $des;
            foreach (($c['impuestos'] ?? []) as $i) {
                if (strtolower($i['factor'] ?? '') === 'exento') continue;
                $tasa = (float)($i['tasa'] ?? 0) / 100;
                $m = $base * $tasa;
                $impuestos += (($i['tipo'] ?? 'T')==='R') ? -$m : $m;
            }
        }
        $total = $subtotal - $descuento + $impuestos;

        $borradorId   = isset($p['borrador_id']) ? (int)$p['borrador_id'] : null;
        $rfcUsuarioId = (int) session('rfc_usuario_id');

        if ($borradorId) {
            $b = \App\Models\FacturaBorrador::where('id',$borradorId)
                ->when($rfcUsuarioId, fn($q)=>$q->where('rfc_usuario_id',$rfcUsuarioId))
                ->first();
            if (!$b) return back()->with('error','No se encontró el borrador a actualizar.');
        } else {
            $b = new \App\Models\FacturaBorrador();
            $b->user_id        = auth()->id();
            $b->rfc_usuario_id = $rfcUsuarioId;
        }

        $b->cliente_id      = (int) $p['cliente_id'];
        $b->tipo            = $p['tipo_comprobante'] ?? 'I';
        $b->serie           = $p['serie'] ?? null;
        $b->folio           = (string)($p['folio'] ?? '');
        $b->fecha           = $p['fecha'] ?? now();
        $b->metodo_pago     = $p['metodo_pago'] ?? 'PUE';
        $b->forma_pago      = $p['forma_pago'] ?? '99';
        $b->comentarios_pdf = $this->extraerComentarios($p);
        $b->subtotal        = round($subtotal,2);
        $b->descuento       = round($descuento,2);
        $b->impuestos       = round($impuestos,2);
        $b->total           = round($total,2);
        $b->payload         = $p;
        $b->estatus         = 'borrador';
        $b->save();

        return redirect()->route('borradores.index')->with('ok','Borrador '.($borradorId?'actualizado':'guardado').' (#'.$b->id.').');
    }

    public function timbrar(Request $r)
    {
        return back()->with('ok','Timbrado enviado (placeholder).');
    }

    // ---------- Helpers ----------
    private function extraerComentarios(array $p): string
    {
        foreach (['comentarios_pdf','comentariosPDF','comentarios','observaciones','notas','nota'] as $k) {
            if (!empty($p[$k]) && is_string($p[$k])) return (string)$p[$k];
        }
        if (!empty($p['meta']['comentarios_pdf'])) return (string)$p['meta']['comentarios_pdf'];
        return '';
    }

    private function normalizarRelacionados(array $p): array
    {
        $candidatos = ['relacionados','cfdi_relacionados','documentos_relacionados','Relacionados','CfdiRelacionados'];
        $data = null;
        foreach ($candidatos as $k) {
            if (isset($p[$k])) { $data = $p[$k]; break; }
        }
        if ($data === null && (isset($p['tipo_relacion']) || isset($p['TipoRelacion']))) {
            $data = [$p];
        }
        if ($data === null) return [];
        if (!is_array($data)) return [];
        if (isset($data['tipo_relacion']) || isset($data['TipoRelacion']) || isset($data['uuids']) || isset($data['UUIDs']) || isset($data['uuid']) || isset($data['UUID'])) {
            $data = [$data];
        }

        $out = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                if (is_string($item) && strlen($item) >= 10) $out[] = ['tipo_relacion'=>'','uuids'=>[$item]];
                continue;
            }
            $tipo  = $item['tipo_relacion'] ?? $item['TipoRelacion'] ?? $item['tipo'] ?? '';
            $uuids = $item['uuids'] ?? $item['UUIDs'] ?? $item['cfdis'] ?? $item['Cfdis'] ?? $item['lista'] ?? null;
            if ($uuids === null) {
                // Soporta uuid singular (minúscula o mayúscula)
                if (isset($item['uuid'])) $uuids = [$item['uuid']];
                elseif (isset($item['UUID'])) $uuids = [$item['UUID']];
            }
            if (is_string($uuids)) $uuids = preg_split('/[|,;\s]+/', $uuids, -1, PREG_SPLIT_NO_EMPTY);
            if (!is_array($uuids)) $uuids = [];
            $uuids = array_values(array_filter(array_map('trim',$uuids), fn($u)=> is_string($u) && strlen($u) > 10));
            if (!count($uuids)) continue;

<<<<<<< HEAD
            $out[] = ['tipo_relacion'=>(string)$tipo, 'uuids'=>$uuids];
=======
            $out[] = [
                'tipo_relacion' => (string)$tipo,
                'uuids'         => $uuids,
            ];
>>>>>>> parent of c14a727 (cambios)
        }
        return $out;
    }
}
