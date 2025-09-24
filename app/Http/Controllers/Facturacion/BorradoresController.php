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

        $clientes = DB::table('clientes')
            ->whereIn('id', $borradores->pluck('cliente_id')->all())
            ->pluck('razon_social','id');

        return view('facturacion.borradores.index', compact('borradores','clientes'));
    }

    public function edit(FacturaBorrador $borrador)
    {
        // Reutiliza la vista de crear con payload precargado
        $rfcActivo    = session('rfc_seleccionado');
        $rfcUsuarioId = (int) session('rfc_usuario_id');

        $clientes = DB::table('clientes')->orderBy('razon_social')->get([
            'id','rfc','razon_social','email','calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais'
        ]);

        $formasPago = collect([
            ['clave'=>'01','descripcion'=>'Efectivo'],
            ['clave'=>'02','descripcion'=>'Cheque nominativo'],
            ['clave'=>'03','descripcion'=>'Transferencia electrónica de fondos'],
            ['clave'=>'04','descripcion'=>'Tarjeta de crédito'],
            ['clave'=>'28','descripcion'=>'Tarjeta de débito'],
            ['clave'=>'29','descripcion'=>'Tarjeta de servicios'],
            ['clave'=>'99','descripcion'=>'Por definir'],
        ]);
        $metodosPago = collect([
            ['clave'=>'PUE','descripcion'=>'Pago en una sola exhibición'],
            ['clave'=>'PPD','descripcion'=>'Pago en parcialidades o diferido'],
        ]);

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
            'borrador'      => $borrador, // <- Alpine lo usará para precargar
        ]);
    }

    public function destroy(FacturaBorrador $borrador)
    {
        $rfcUsuarioId = (int) session('rfc_usuario_id');
        if ($rfcUsuarioId && $borrador->rfc_usuario_id != $rfcUsuarioId) abort(403);
        $borrador->delete();
        return back()->with('ok','Borrador eliminado.');
    }
}
