<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoStoreRequest;
use App\Http\Requests\ProductoUpdateRequest;
use App\Models\Producto;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;

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
}
