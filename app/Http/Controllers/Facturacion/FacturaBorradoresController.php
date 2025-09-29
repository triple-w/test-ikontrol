<?php

namespace App\Http\Controllers\Facturacion;

use App\Http\Controllers\Controller;
use App\Models\FacturaBorrador;
use Illuminate\Http\Request;

class FacturaBorradoresController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q',''));
        $rows = FacturaBorrador::query()
            ->when($q, function($w) use ($q){
                $w->where('serie', 'like', "%$q%")
                  ->orWhere('folio', 'like', "%$q%")
                  ->orWhere('comentarios_pdf', 'like', "%$q%");
            })
            ->orderByDesc('id')
            ->paginate(20);

        return view('facturacion.facturas.borradores.index', compact('rows','q'));
    }

    // Abre el CREATE precargando payload del borrador
    public function openInCreate(FacturaBorrador $borrador)
    {
        // Puedes validar ownership si aplica:
        // abort_unless($borrador->user_id === auth()->id(), 403);

        $payload = $borrador->payload ?? [];

        // Inyecta cliente_id si falta en payload:
        if (!isset($payload['cliente_id']) && $borrador->cliente_id) {
            $payload['cliente_id'] = (int) $borrador->cliente_id;
        }

        session(['factura_prefill' => $payload]);

        return redirect()
            ->route('facturas.create')
            ->with('ok', 'Borrador #'.$borrador->id.' cargado en creación.');
    }

    public function destroy(FacturaBorrador $borrador)
    {
        // abort_unless($borrador->user_id === auth()->id(), 403);
        $borrador->delete();
        return back()->with('ok', 'Borrador eliminado.');
    }
}
