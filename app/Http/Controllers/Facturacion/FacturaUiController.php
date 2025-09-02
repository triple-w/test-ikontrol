<?php
namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\SeriesFolio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FacturaUiController extends Controller
{
    public function create(Request $request)
        {
            $rfcActivo = session('rfc_seleccionado');
            $emisor = \Auth::user()->rfcs->firstWhere('rfc', $rfcActivo);

            // 👇 columnas reales de tu tabla clientes
            $clientes = \App\Models\Cliente::orderBy('razon_social')->get([
                'id',
                'razon_social',
                'rfc',
                'calle',
                'no_ext',
                'no_int',
                'colonia',
                'localidad',
                'estado',
                'codigo_postal',
                'pais',
                'email',
            ]);

            $seedProds = \App\Models\Producto::orderBy('descripcion')->limit(20)->get([
                'id','descripcion','precio','clave_prod_serv_id','clave_unidad_id','unidad'
            ]);

            $max = \Carbon\Carbon::now();
            $min = (clone $max)->subHours(72);

            // 👇 apunta a resources/views/facturacion/facturas/create.blade.php
            return view('facturacion.facturas.create', [
                'emisor'   => $emisor,
                'clientes' => $clientes,
                'seedProds'=> $seedProds,
                'fechaMin' => $min->format('Y-m-d\TH:i'),
                'fechaMax' => $max->format('Y-m-d\TH:i'),
            ]);
        }

    // Serie/Folio automáticos por tipo (I,E,P,N)
    public function nextFolio(Request $request)
    {
        $request->validate(['tipo' => 'required|in:I,E,P,N']);
        $rfcId = optional(Auth::user()->rfcs->firstWhere('rfc', session('rfc_seleccionado')))->id;

        $defSerie = [
            'I' => 'F',   // Factura ingreso
            'E' => 'NC',  // Nota de crédito
            'P' => 'CP',  // Complemento de pago
            'N' => 'NOM', // Nómina
        ][$request->tipo];

        $cfg = SeriesFolio::firstOrCreate(
            ['rfc_id' => $rfcId, 'tipo_comprobante' => $request->tipo],
            ['serie' => $defSerie, 'ultimo_folio' => 0]
        );

        return response()->json([
            'serie' => $cfg->serie,
            'folio' => $cfg->ultimo_folio + 1,
        ]);
    }

    // Autocompletar productos
    public function buscarProductos(Request $request)
    {
        $q = trim($request->get('q',''));
        if ($q === '') return response()->json([]);

        $prods = Producto::where('descripcion','like',"%{$q}%")
            ->orderBy('descripcion')->limit(20)
            ->get(['id','descripcion','precio','clave_prod_serv_id','clave_unidad_id','unidad']);

        return response()->json($prods);
    }

    // Vista previa (HTML simple por ahora)
    public function preview(Request $request)
    {
        // solo validación básica; la validación fuerte la pondrás en StoreFacturaRequest cuando timbres
        $data = $request->validate([
            'encabezado' => 'required|array',
            'cliente'    => 'required|array',
            'conceptos'  => 'required|array|min:1',
            'relaciones' => 'array',
            'totales'    => 'array',
        ]);

        return view('facturacion.facturas.preview', $data); // crea esta vista con tu invoice
    }

    // Placeholder de guardado/timbrado
    public function store(Request $request)
    {
        // Aquí harás: validación SAT, armado de XML, timbrado PAC, envío correo, decremento de timbres, etc.
        return back()->with('status', 'Guardado (placeholder)');
    }
}
