<?php
namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\SeriesFolio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FacturaUiController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        // RFC activo desde sesión (asegurado por tu middleware)
        $rfcId = (int) session('rfc_usuario_id') ?: (int) session('rfc_activo_id');

        // Clientes del RFC
        $clientes = DB::table('clientes')
            ->when(Schema::hasColumn('clientes', 'rfc_usuario_id'), fn($q) => $q->where('rfc_usuario_id', $rfcId))
            ->orderBy('razon_social', 'asc')
            ->get([
                'id','rfc','razon_social','calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais',
                Schema::hasColumn('clientes','email') ? 'email' : DB::raw("'' as email"),
            ]);

        // Fechas
        $now = Carbon::now();
        $maxFecha = $now->format('Y-m-d\TH:i');                // ahora
        $minFecha = $now->copy()->subHours(72)->format('Y-m-d\TH:i'); // -72h

        // Serie/Folio por defecto para tipo Ingreso (I)
        $serieDefault = $this->nextFolioData($rfcId, 'I');

        return view('facturacion.facturas.create', [
            'rfcUsuarioId' => $rfcId,
            'clientes'     => $clientes,
            'minFecha'     => $minFecha,
            'maxFecha'     => $maxFecha,
            'serieDefault' => $serieDefault, // ['serie'=>'X','folio'=>123] | null
        ]);
    }

    // Serie/Folio automáticos por tipo (I,E,P,N)
    public function nextFolio(Request $request)
    {
        $request->validate([
            'tipo' => 'nullable|string|max:2'
        ]);

        $tipo = strtoupper($request->input('tipo', 'I'));
        $rfcId = (int) session('rfc_usuario_id') ?: (int) session('rfc_activo_id');

        $data = $this->nextFolioData($rfcId, $tipo);

        if (!$data) {
            return response()->json(['ok' => false, 'message' => 'No hay serie/folio configurado para este tipo.'], 404);
        }
        return response()->json(['ok' => true] + $data);
    }

    // Autocompletar productos
    private function nextFolioData(int $rfcId, string $tipo): ?array
    {
        // Tabla 'folios' puede tener 'tipo_comprobante' o 'tipo'
        $colTipo = Schema::hasColumn('folios', 'tipo_comprobante') ? 'tipo_comprobante' :
                   (Schema::hasColumn('folios', 'tipo') ? 'tipo' : null);

        $query = DB::table('folios')->where('rfc_usuario_id', $rfcId);
        if ($colTipo) {
            $query->where($colTipo, $tipo);
        }

        $folio = $query->orderByDesc('id')->first(); // último activo si tienes 'activo', aquí el último

        if (!$folio) return null;

        // Maneja distintos nombres de columnas según tu esquema
        $serie        = $folio->serie ?? '';
        $folioActual  = $folio->folio_actual ?? ($folio->ultimo_folio ?? 0);
        $folioInicial = $folio->folio_inicial ?? 1;
        $folioFinal   = $folio->folio_final   ?? null;

        $siguiente = max((int)$folioActual + 1, (int)$folioInicial);
        if ($folioFinal && $siguiente > (int)$folioFinal) {
            return null; // agotado
        }

        return [
            'serie' => (string)$serie,
            'folio' => (int)$siguiente,
        ];
    }

    public function buscarProductos(Request $request)
    {
        $term  = trim((string)$request->input('term', ''));
        $rfcId = (int) session('rfc_usuario_id') ?: (int) session('rfc_activo_id');

        $q = DB::table('productos')
            ->when(Schema::hasColumn('productos','rfc_usuario_id'), fn($qq)=>$qq->where('rfc_usuario_id',$rfcId))
            ->when($term !== '', function ($qq) use ($term) {
                $qq->where(function ($w) use ($term) {
                    $w->where('descripcion','like',"%{$term}%");
                    if (Schema::hasColumn('productos','no_identificacion')) {
                        $w->orWhere('no_identificacion','like',"%{$term}%");
                    }
                });
            })
            ->orderBy('descripcion','asc')
            ->limit(20);

        // Campos flexibles según tu esquema
        $selects = [
            'id','descripcion',
            Schema::hasColumn('productos','precio') ? 'precio' : DB::raw('0 as precio'),
            Schema::hasColumn('productos','unidad') ? 'unidad' : DB::raw("'' as unidad"),
        ];

        // Claves SAT (id + código legible si existe)
        $hasCPSid  = Schema::hasColumn('productos','clave_prod_serv_id');
        $hasCPS    = Schema::hasColumn('productos','clave_prod_serv');
        $hasCUID   = Schema::hasColumn('productos','clave_unidad_id');
        $hasCU     = Schema::hasColumn('productos','clave_unidad');

        if ($hasCPSid)  $selects[] = 'clave_prod_serv_id';
        if ($hasCPS)    $selects[] = 'clave_prod_serv';
        if ($hasCUID)   $selects[] = 'clave_unidad_id';
        if ($hasCU)     $selects[] = 'clave_unidad';
        if (Schema::hasColumn('productos','objeto_imp')) $selects[] = 'objeto_imp';
        if (Schema::hasColumn('productos','no_identificacion')) $selects[] = 'no_identificacion';

        $productos = $q->get($selects)->map(function ($p) use ($hasCPSid,$hasCPS,$hasCUID,$hasCU) {
            // Normaliza campos de clave y código
            $cps_code = $hasCPS ? ($p->clave_prod_serv ?? null) : null;
            $cu_code  = $hasCU  ? ($p->clave_unidad    ?? null) : null;

            return [
                'id'                   => $p->id,
                'descripcion'          => $p->descripcion,
                'precio'               => (float)($p->precio ?? 0),
                'unidad'               => (string)($p->unidad ?? ''),
                'clave_prod_serv_id'   => $hasCPSid ? ($p->clave_prod_serv_id ?? null) : null,
                'clave_unidad_id'      => $hasCUID ? ($p->clave_unidad_id    ?? null) : null,
                'clave_prod_serv_code' => $cps_code,
                'clave_unidad_code'    => $cu_code,
                'objeto_imp'           => property_exists($p,'objeto_imp') ? ($p->objeto_imp ?? '02') : '02',
                'no_identificacion'    => property_exists($p,'no_identificacion') ? ($p->no_identificacion ?? null) : null,
            ];
        });

        return response()->json([
            'ok' => true,
            'items' => $productos,
        ]);
    }

    public function preview(Request $request)
    {
        // Por ahora solo renderizamos vista con los datos que manda Alpine (JSON serializado).
        // Luego validamos CFDI 4.0 y armamos XML.
        $payload = $request->input('data'); // string JSON desde el form
        $data = json_decode($payload, true) ?: [];

        return view('facturacion.facturas.preview', compact('data'));
    }


    // Placeholder de guardado/timbrado
    public function store(Request $request)
    {
        // Aquí harás: validación SAT, armado de XML, timbrado PAC, envío correo, decremento de timbres, etc.
        return back()->with('status', 'Guardado (placeholder)');
    }
}
