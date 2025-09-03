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

        // 1) Obtén el RFC activo desde sesión (compatibles con nombres anteriores)
        $rfcUsuarioId = session('rfc_usuario_id') ?? session('rfc_activo_id');

        // 2) Si no hubiera en sesión, toma el primero del usuario (detectando la FK)
        if (!$rfcUsuarioId) {
            $colUserRU = null;
            foreach (['users_id', 'user_id', 'usuario_id'] as $c) {
                if (Schema::hasColumn('rfc_usuarios', $c)) { $colUserRU = $c; break; }
            }
            $q = DB::table('rfc_usuarios');
            if ($colUserRU) $q->where($colUserRU, $user->id);
            $rfcUsuarioId = $q->orderBy('id')->value('id');
            if ($rfcUsuarioId) {
                session(['rfc_usuario_id' => $rfcUsuarioId]);
            }
        }

        // 3) RFC texto para mostrar en la cabecera
        $rfcActivo = DB::table('rfc_usuarios')->where('id', $rfcUsuarioId)->value('rfc') ?? '—';

        // 4) Lista de clientes con los campos que pediste
        $clientes = DB::table('clientes')
            ->when(Schema::hasColumn('clientes', 'rfc_usuario_id'),
                fn($q) => $q->where('rfc_usuario_id', $rfcUsuarioId))
            ->orderBy('razon_social', 'asc')
            ->get([
                'id','rfc','razon_social',
                'calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais',
                'email',
            ]);

        return view('facturacion.facturas.create', [
            'rfcUsuarioId' => (int) $rfcUsuarioId,
            'rfcActivo'    => $rfcActivo,
            'clientes'     => $clientes,
        ]);
    }

    public function nextFolio(Request $request)
    {
        $tipo = $request->query('tipo', 'I'); // I=Ingreso, E=Egreso, P=Pago, T=Traslado
        $rfcUsuarioId = (int) session('rfc_usuario_id'); // RFC ACTIVO

        // Folios: asumo columnas: rfc_usuario_id, tipo_comprobante (I/E/P/T), serie, ultimo_folio
        $folio = \DB::table('folios')
            ->where('rfc_usuario_id', $rfcUsuarioId)
            ->where('tipo_comprobante', $tipo)
            ->orderBy('id')
            ->first();

        if (!$folio) {
            return response()->json([
                'serie' => null,
                'folio' => null,
            ]);
        }

        return response()->json([
            'serie' => $folio->serie,
            'folio' => (int) $folio->ultimo_folio + 1,
        ]);
    }

    // Autocompletar productos
    public function buscarProductos(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $items = \DB::table('productos')
            ->select([
                'id',
                'descripcion',
                'precio',
                'unidad', // texto (p.e. "Pieza")
                'clave_prod_serv_id', // <- tú nos dijiste que ahora terminan en _id
                'clave_unidad_id',    // <- idem
                'objeto_imp',         // "01"=No objeto, "02"=Sí objeto
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('descripcion', 'like', "%{$q}%")
                    ->orWhere('clave_prod_serv_id', 'like', "%{$q}%")
                    ->orWhere('clave_unidad_id', 'like', "%{$q}%");
                });
            })
            ->orderBy('descripcion', 'asc')
            ->limit(20)
            ->get();

        return response()->json($items);
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
