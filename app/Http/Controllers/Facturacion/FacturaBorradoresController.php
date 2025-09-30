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
        $payload = $borrador->payload ?? [];
        if (!isset($payload['cliente_id']) && $borrador->cliente_id) {
            $payload['cliente_id'] = (int) $borrador->cliente_id;
        }
        session(['factura_restore_payload' => $payload]); // <--- esta clave

        return redirect()->route('facturas.create')
                        ->with('ok', 'Borrador #'.$borrador->id.' cargado en creación.');
    }

    public function loadIntoCreate(FacturaBorrador $borrador)
    {
        // Alias para compatibilidad con la ruta/vista que llama a loadIntoCreate
        return $this->openInCreate($borrador);
    }

    public function destroy(FacturaBorrador $borrador)
    {
        // abort_unless($borrador->user_id === auth()->id(), 403);
        $borrador->delete();
        return back()->with('ok', 'Borrador eliminado.');
    }

    public function store(Request $r)
    {
        $payload = json_decode($r->input('payload','{}'), true) ?: [];

        if (!isset($payload['cliente_id']) || !isset($payload['conceptos'])) {
            return back()->with('error','Payload incompleto.');
        }

        // Recalcula totales (mismo cálculo que usas en FacturaUiController@guardar)
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

        $b = new \App\Models\FacturaBorrador();
        $b->user_id        = auth()->id();
        $b->rfc_usuario_id = (int) session('rfc_usuario_id');
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

        $b->payload        = $payload;
        $b->estatus        = 'borrador';
        $b->save();

        return back()->with('ok', 'Borrador guardado (#'.$b->id.').');
    }

}
