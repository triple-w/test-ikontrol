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

    public function update(\App\Http\Requests\ClienteUpdateRequest $request, \App\Models\Cliente $cliente)
    {
        $rfc = session('rfc_seleccionado');
        abort_unless($rfc, 403);
        abort_unless(optional($cliente->rfcUsuario)->rfc === $rfc, 403);

        $cliente->update($request->validated());

        if ($request->expectsJson()) {
            // Devuelve los campos que usa la vista
            return response()->json([
                'id'             => $cliente->id,
                'rfc'            => $cliente->rfc,
                'razon_social'   => $cliente->razon_social,
                'calle'          => $cliente->calle,
                'no_ext'         => $cliente->no_ext,
                'no_int'         => $cliente->no_int,
                'colonia'        => $cliente->colonia,
                'localidad'      => $cliente->localidad,
                'estado'         => $cliente->estado,
                'codigo_postal'  => $cliente->codigo_postal,
                'pais'           => $cliente->pais,
                'email'          => $cliente->email,
            ]);
        }

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

    public function quickUpdate(\Illuminate\Http\Request $request, \App\Models\Cliente $cliente)
    {
        $data = $request->validate([
            'razon_social'  => 'required|string|max:255',
            'rfc'           => 'required|string|max:13',
            'email'         => 'nullable|email|max:255',
            'calle'         => 'nullable|string|max:255',
            'no_ext'        => 'nullable|string|max:50',
            'no_int'        => 'nullable|string|max:50',
            'colonia'       => 'nullable|string|max:255',
            'localidad'     => 'nullable|string|max:255',
            'estado'        => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
            'pais'          => 'nullable|string|max:100',
        ]);

        $cliente->update($data);

        return response()->json([
            'id'             => $cliente->id,
            'rfc'            => $cliente->rfc,
            'razon_social'   => $cliente->razon_social,
            'email'          => $cliente->email,
            'calle'          => $cliente->calle,
            'no_ext'         => $cliente->no_ext,
            'no_int'         => $cliente->no_int,
            'colonia'        => $cliente->colonia,
            'localidad'      => $cliente->localidad,
            'estado'         => $cliente->estado,
            'codigo_postal'  => $cliente->codigo_postal,
            'pais'           => $cliente->pais,
        ]);
    }


}
