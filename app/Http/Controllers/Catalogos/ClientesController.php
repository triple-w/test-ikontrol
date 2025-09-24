<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cliente;

class ClientesController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('razon_social')->paginate(20);
        return view('catalogos.clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('catalogos.clientes.create');
    }

    public function store(Request $request)
    {
        $data = $this->rules($request);
        $cliente = Cliente::create($data);
        return redirect()->route('clientes.index')->with('ok', 'Cliente creado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('catalogos.clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $data = $this->rules($request);
        $cliente->update($data);

        if ($request->expectsJson()) {
            return response()->json($cliente->only([
                'id','rfc','razon_social','email','calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais'
            ]));
        }
        return redirect()->route('clientes.index')->with('ok','Cliente actualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return back()->with('ok','Cliente eliminado.');
    }

    /**
     * Quick update desde el modal lateral en create de facturas.
     */
    public function quickUpdate(Request $request, Cliente $cliente)
    {
        $data = $this->rules($request);
        $cliente->update($data);

        return response()->json($cliente->only([
            'id','rfc','razon_social','email','calle','no_ext','no_int','colonia','localidad','estado','codigo_postal','pais'
        ]));
    }

    private function rules(Request $request): array
    {
        return $request->validate([
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
    }
}
