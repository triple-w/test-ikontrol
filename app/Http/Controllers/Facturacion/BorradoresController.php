<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\FacturaBorrador;

class BorradoresController extends Controller
{
    public function index(Request $r)
    {
        $rfcUsuarioId = (int) session('rfc_usuario_id');

        $borradores = FacturaBorrador::query()
            ->when($rfcUsuarioId, fn($q)=>$q->where('rfc_usuario_id',$rfcUsuarioId))
            ->orderByDesc('id')
            ->get();

        // Trae nombre de cliente
        $clientes = DB::table('clientes')->whereIn('id', $borradores->pluck('cliente_id')->all())
                     ->pluck('razon_social','id');

        return view('facturacion.borradores.index', compact('borradores','clientes'));
    }

    public function edit(FacturaBorrador $borrador)
    {
        // Reutilizamos la vista de crear factura, precargando el payload (ver create.blade con opts.initial)
        // Trae los mismos datos que usa FacturaUiController@create:
        $rfcActivo    = session('rfc_seleccionado');
        $rfcUsuarioId = (int) session('rfc_usuario_id');

        $clientes = DB::table('clientes')->orderBy('razon_social')->get([
            'id','rfc','razon_social','email',
            'calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais'
        ]);

        // Métodos/formas: usa los mismos fallbacks de tu FacturaUiController
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

        if (\Schema::hasTable('sat_forma_pago')) {
            $formasPago = DB::table('sat_forma_pago')->orderBy('clave')->get(['clave','descripcion']);
            if ($formasPago->isEmpty()) $formasPago = $fallbackFormasPago;
        } elseif (\Schema::hasTable('c_forma_pago')) {
            $formasPago = DB::table('c_forma_pago')->orderBy('Clave')->get()
                ->map(fn($r)=>['clave'=>$r->Clave,'descripcion'=>$r->Descripcion]);
            if ($formasPago->isEmpty()) $formasPago = $fallbackFormasPago;
        } else $formasPago = $fallbackFormasPago;

        if (\Schema::hasTable('sat_metodo_pago')) {
            $metodosPago = DB::table('sat_metodo_pago')->whereIn('clave',['PUE','PPD'])->orderBy('clave')->get(['clave','descripcion']);
            if ($metodosPago->isEmpty()) $metodosPago = $fallbackMetodosPago;
        } elseif (\Schema::hasTable('c_metodo_pago')) {
            $metodosPago = DB::table('c_metodo_pago')->whereIn('Clave',['PUE','PPD'])->orderBy('Clave')->get()
                ->map(fn($r)=>['clave'=>$r->Clave,'descripcion'=>$r->Descripcion]);
            if ($metodosPago->isEmpty()) $metodosPago = $fallbackMetodosPago;
        } else $metodosPago = $fallbackMetodosPago;

        $minFecha = now()->copy()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha = now()->format('Y-m-d\TH:i');

        // Reutiliza la vista de crear, pero pasando $borrador
        return view('facturacion.facturas.create', [
            'rfcActivo'     => $rfcActivo,
            'rfcUsuarioId'  => $rfcUsuarioId,
            'clientes'      => $clientes,
            'formasPago'    => $formasPago,
            'metodosPago'   => $metodosPago,
            'minFecha'      => $minFecha,
            'maxFecha'      => $maxFecha,
            'borrador'      => $borrador, // <- para precargar (opts.initial)
        ]);
    }

    public function destroy(FacturaBorrador $borrador)
    {
        $rfcUsuarioId = (int) session('rfc_usuario_id');
        if ($rfcUsuarioId && $borrador->rfc_usuario_id != $rfcUsuarioId) {
            abort(403);
        }
        $borrador->delete();
        return back()->with('ok','Borrador eliminado.');
    }
}
