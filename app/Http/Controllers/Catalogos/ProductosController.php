<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoStoreRequest;
use App\Http\Requests\ProductoUpdateRequest;
use App\Models\Producto;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductosController extends Controller
{
    public function index(Request $r)
    {
        $items = Producto::with(['prodServ:id,clave,descripcion','unidadSat:id,clave,descripcion'])
            ->forActiveRfc()
            ->when($r->filled('buscar'), function($q) use ($r) {
                $t = '%'.$r->buscar.'%';
                $q->where(fn($w)=>$w->where('descripcion','like',$t)->orWhere('clave','like',$t));
            })
            ->orderBy('descripcion')->paginate(20)->withQueryString();

        return view('catalogos.productos.index', compact('items'));
    }

    public function create()
    {
        return view('catalogos.productos.create');
    }

    public function store(ProductoStoreRequest $req)
    {
        $rfc = session('rfc_seleccionado'); abort_unless($rfc, 422, 'RFC activo requerido.');
        $ru  = RfcUsuario::where('user_id', auth()->id())->where('rfc', $rfc)->firstOrFail();

        $data = $req->validated();
        $data['users_id']       = auth()->id();  // compatibilidad
        $data['rfc_usuario_id'] = $ru->id;       // **auto RFC**

        Producto::create($data);
        return redirect()->route('productos.index')->with('ok','Producto creado.');
    }

    public function edit(Producto $producto)
    {
        abort_unless(optional($producto->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
        return view('catalogos.productos.edit', compact('producto'));
    }

    public function update(ProductoUpdateRequest $req, Producto $producto)
    {
        abort_unless(optional($producto->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
        $producto->update($req->validated());
        return redirect()->route('productos.index')->with('ok','Producto actualizado.');
    }

    public function destroy(Producto $producto)
    {
        abort_unless(optional($producto->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
        $producto->delete();
        return redirect()->route('productos.index')->with('ok','Producto eliminado.');
    }

    public function buscar(Request $r)
    {
        $term = trim((string) $r->query('q', ''));
        $rfcId = (int) session('rfc_usuario_id');

        $q = Producto::query()
            ->when($rfcId, fn($q) => $q->where('rfc_usuario_id', $rfcId));

        if ($term !== '') {
            $q->where(function($qq) use ($term) {
                $qq->where('descripcion','like',"%{$term}%")
                ->orWhere('clave','like',"%{$term}%");
            });
        }

        $items = $q->orderBy('descripcion')
            ->limit(20)
            ->get(['id','descripcion','precio','unidad','clave_prod_serv_id','clave_unidad_id']);

        // Formato amigable para select remoto
        $data = $items->map(function ($p) {
            return [
                'id'               => $p->id,
                'text'             => $p->descripcion,
                'descripcion'      => $p->descripcion,
                'precio'           => (float) $p->precio,
                'unidad'           => $p->unidad,
                'clave_prod_serv'  => $p->clave_prod_serv_id,
                'clave_unidad'     => $p->clave_unidad_id,
            ];
        });

        return response()->json(['results' => $data]);
    }

    public function buscarClaveProdServ(Request $r)
    {
        $term = trim((string) $r->query('q', ''));
        $q = DB::table('clave_prod_serv');

        if ($term !== '') {
            $q->where(function($qq) use ($term) {
                $qq->where('clave','like',"%{$term}%")
                ->orWhere('descripcion','like',"%{$term}%");
            });
        }

        $items = $q->orderBy('clave')->limit(20)->get(['id','clave','descripcion']);

        $data = $items->map(fn($i) => [
            'id'   => $i->id,
            'text' => "{$i->clave} — {$i->descripcion}",
            'clave'=> $i->clave,
            'descripcion' => $i->descripcion,
        ]);

        return response()->json(['results' => $data]);
    }

    public function buscarClaveUnidad(Request $r)
    {
        $term = trim((string) $r->query('q', ''));
        $q = DB::table('clave_unidad');

        if ($term !== '') {
            $q->where(function($qq) use ($term) {
                $qq->where('clave','like',"%{$term}%")
                ->orWhere('descripcion','like',"%{$term}%");
            });
        }

        $items = $q->orderBy('clave')->limit(20)->get(['id','clave','descripcion']);

        $data = $items->map(fn($i) => [
            'id'   => $i->id,
            'text' => "{$i->clave} — {$i->descripcion}",
            'clave'=> $i->clave,
            'descripcion' => $i->descripcion,
        ]);

        return response()->json(['results' => $data]);
    }
}
