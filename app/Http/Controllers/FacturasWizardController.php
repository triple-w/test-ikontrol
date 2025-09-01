<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

// Usa tu motor actual:
use App\Http\Controllers\Users\FacturasV33Controller as MotorCFDI;

class FacturasWizardController extends Controller
{
    /** Paso 1 del wizard (formulario) */
    public function create(Request $request)
    {
        $rfcId = (int) session('rfc_usuario_id'); // viene del dropdown/middleware que ya tienes

        // Listas básicas para selects (usa tus catálogos si ya existen)
        $clientes = DB::table('clientes')
            ->when($rfcId, fn($q) => $q->where('rfc_usuario_id', $rfcId))
            ->orderBy('razon_social')->get(['id','rfc','razon_social','codigo_postal','email','regimen_fiscal']);

        $productos = DB::table('productos')
            ->when($rfcId, fn($q) => $q->where('rfc_usuario_id', $rfcId))
            ->orderBy('descripcion')->limit(100)->get([
                'id','descripcion','clave_prod_serv','clave_unidad','unidad','precio','objeto_imp'
            ]);

        // Combos típicos (si ya tienes tablas SAT, sustituye estos arrays por consultas):
        $usosCfdi   = ['G01','G03','S01','CP01','D01','D02','D03','D04','D05','D06','D07','D08','D09','D10','P01']; // ejemplo
        $formasPago = ['01'=>'Efectivo','02'=>'Cheque','03'=>'Transferencia','04'=>'Tarjeta Crédito','28'=>'Tarjeta Débito','99'=>'Por definir'];
        $metodos    = ['PUE'=>'PUE','PPD'=>'PPD'];
        $monedas    = ['MXN'=>'MXN','USD'=>'USD','EUR'=>'EUR'];
        $tiposRelacion = ['01'=>'Nota de crédito','02'=>'Nota de débito','03'=>'Devolución de mercancía','04'=>'Sustitución','07'=>'CFDI por aplicación de anticipo'];

        return view('facturas.wizard', compact(
            'clientes','productos','usosCfdi','formasPago','metodos','monedas','tiposRelacion'
        ));
    }

    /** Autocomplete (opcional) */
    public function clientes(Request $request)
    {
        $rfcId = (int) session('rfc_usuario_id');
        $q = trim($request->query('q', ''));
        return DB::table('clientes')
            ->when($rfcId, fn($qb) => $qb->where('rfc_usuario_id', $rfcId))
            ->when($q, fn($qb) => $qb->where(function($qq) use ($q) {
                $qq->where('razon_social','like',"%$q%")
                   ->orWhere('rfc','like',"%$q%");
            }))
            ->orderBy('razon_social')
            ->limit(20)
            ->get(['id','rfc','razon_social','codigo_postal','email','regimen_fiscal']);
    }

    /** Autocomplete (opcional) */
    public function productos(Request $request)
    {
        $rfcId = (int) session('rfc_usuario_id');
        $q = trim($request->query('q', ''));
        return DB::table('productos')
            ->when($rfcId, fn($qb) => $qb->where('rfc_usuario_id', $rfcId))
            ->when($q, fn($qb) => $qb->where(function($qq) use ($q) {
                $qq->where('descripcion','like',"%$q%")
                   ->orWhere('clave_prod_serv','like',"%$q%");
            }))
            ->orderBy('descripcion')
            ->limit(20)
            ->get(['id','descripcion','clave_prod_serv','clave_unidad','unidad','precio','objeto_imp']);
    }

    /** Último paso del wizard: validar y delegar en tu motor (postAdd) */
    public function timbrar(Request $request)
    {
        // Validación mínima (ajusta si usas catálogos SAT en BD)
        Validator::make($request->all(), [
            'tipo'                  => 'required|in:I,E,P,T', // I=Ingreso, E=Egreso, P=Pago, T=Traslado
            'cliente_id'            => 'required|integer|exists:clientes,id',
            'fechaFactura'          => 'required|date',
            'tipoMoneda'            => 'required|string|max:3',
            'usoCFDI'               => 'required|string|max:5',
            'formaPago'             => 'nullable|string|max:3',
            'metodoPago'            => 'nullable|string|max:3',
            'claves-prods-servs'    => 'required|array|min:1',
            'claves-unidades'       => 'required|array|min:1',
            'descripciones'         => 'required|array|min:1',
            'cantidad'              => 'required|array|min:1',
            'precios'               => 'required|array|min:1',
            // impuestos opcionales como arrays paralelos: traslados-tasap[], traslados-tipop[], retenciones-tasap[], retenciones-tipop[]
        ])->validate();

        $rfcId = (int) session('rfc_usuario_id');
        $cliente = DB::table('clientes')->where('id', $request->cliente_id)->first();

        // Mapear campos al formato que tu FacturasV33Controller::postAdd espera.
        // (lo deduje leyendo el controlador que subiste: nombres como 'rfc', 'razon_social', 'codigoPostal', etc.)
        $merge = [
            'rfc'           => $cliente->rfc ?? '',
            'razon_social'  => $cliente->razon_social ?? '',
            'codigoPostal'  => $cliente->codigo_postal ?? '',
            'email'         => $cliente->email ?? '',
            // Si tu motor usa regimen del receptor:
            'regimen'       => $cliente->regimen_fiscal ?? null,
            // Aseguramos RFC activo:
            'rfc_usuario_id'=> $rfcId,
        ];

        // Serie/folio opcional; si manejas folios por tabla, aquí puedes resolverlos.
        if (!$request->filled('serie'))   $merge['serie'] = null;
        if (!$request->filled('folio'))   $merge['folio'] = null;

        $request->merge($merge);

        // Finalmente, delegamos el timbrado al motor actual
        // (usa tal cual tu flujo actual; si tu método es otro, ajústalo)
        return app(MotorCFDI::class)->postAdd($request);
    }
}
