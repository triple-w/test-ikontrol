<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClienteStoreRequest;
use App\Http\Requests\ClienteUpdateRequest;
use App\Models\Cliente;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;

class ClientesController extends Controller
{
    public function index(Request $request)
    {
        $q = Cliente::with('rfcUsuario:id,rfc,razon_social')
            ->forActiveRfc()
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $term = '%'.$request->buscar.'%';
                $query->where(function ($q2) use ($term) {
                    $q2->where('razon_social','like',$term)
                       ->orWhere('rfc','like',$term)
                       ->orWhere('email','like',$term);
                });
            })
            ->orderBy('razon_social');

        $clientes = $q->paginate(20)->withQueryString();

        return view('catalogos.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('catalogos.clientes.create');
    }

    public function store(ClienteStoreRequest $request)
    {
        $rfcActivo = session('rfc_seleccionado');
        abort_unless($rfcActivo, 422, 'No hay RFC activo en sesión.');

        $ru = RfcUsuario::where('user_id', auth()->id())
            ->where('rfc', $rfcActivo)
            ->firstOrFail();

        $data = $request->validated();
        $data['users_id'] = auth()->id();       // compatibilidad con tu esquema actual
        $data['rfc_usuario_id'] = $ru->id;      // **asignación automática**

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('ok', 'Cliente creado.');
    }

    public function edit(Cliente $cliente)
    {
        // Seguridad: solo permitir editar si pertenece al RFC activo
        $rfc = session('rfc_seleccionado');
        abort_unless($rfc, 403);
        abort_unless(optional($cliente->rfcUsuario)->rfc === $rfc, 403);

        return view('catalogos.clientes.edit', compact('cliente'));
    }

    public function update(ClienteUpdateRequest $request, Cliente $cliente)
    {
        $rfc = session('rfc_seleccionado');
        abort_unless($rfc, 403);
        abort_unless(optional($cliente->rfcUsuario)->rfc === $rfc, 403);

        $data = $request->validated();
        // mantenemos users_id y rfc_usuario_id como estaban (el RFC activo no cambia aquí)
        $cliente->update($data);

        return redirect()->route('clientes.index')->with('ok', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $rfc = session('rfc_seleccionado');
        abort_unless($rfc, 403);
        abort_unless(optional($cliente->rfcUsuario)->rfc === $rfc, 403);

        $cliente->delete();

        return redirect()->route('clientes.index')->with('ok', 'Cliente eliminado.');
    }
}
