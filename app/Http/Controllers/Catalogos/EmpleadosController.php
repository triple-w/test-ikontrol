<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmpleadoStoreRequest;
use App\Http\Requests\EmpleadoUpdateRequest;
use App\Models\Empleado;
use Illuminate\Http\Request;

class EmpleadosController extends Controller
{
    public function index(Request $r)
    {
        $q = Empleado::query()
            ->with('rfcUsuario:id,rfc,razon_social')
            ->where('users_id', auth()->id())
            ->forActiveRfc()
            ->when($r->filled('buscar'), function($qq) use ($r) {
                $t = '%'.$r->buscar.'%';
                $qq->where(function($w) use ($t) {
                    $w->where('nombre','like',$t)
                      ->orWhere('rfc','like',$t)
                      ->orWhere('numero_empleado','like',$t)
                      ->orWhere('puesto','like',$t);
                });
            })
            ->orderBy('nombre');

        $items = $q->paginate(20)->withQueryString();

        return view('catalogos.empleados.index', compact('items'));
    }

    public function create()
    {
        $empleado = new Empleado();
        return view('catalogos.empleados.create', compact('empleado'));
    }

    public function store(EmpleadoStoreRequest $request)
    {
        $rfcSeleccionado = session('rfc_seleccionado');
        abort_unless($rfcSeleccionado, 422, 'Selecciona un RFC activo.');

        Empleado::create(
            array_merge(
                $request->validated(),
                [
                    'users_id'       => auth()->id(),
                    'rfc_usuario_id' => \App\Models\RfcUsuario::where('rfc', $rfcSeleccionado)->value('id'),
                ]
            )
        );

        return redirect()->route('empleados.index')->with('ok', 'Empleado creado.');
    }

    public function edit(Empleado $empleado)
    {
        // Asegurar pertenencia al RFC activo
        abort_unless(optional($empleado->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);
        return view('catalogos.empleados.edit', compact('empleado'));
    }

    public function update(EmpleadoUpdateRequest $request, Empleado $empleado)
    {
        abort_unless(optional($empleado->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);

        $empleado->update($request->validated());

        return redirect()->route('empleados.index')->with('ok', 'Empleado actualizado.');
    }

    public function destroy(Empleado $empleado)
    {
        abort_unless(optional($empleado->rfcUsuario)->rfc === session('rfc_seleccionado'), 403);

        $empleado->delete();

        return back()->with('ok', 'Empleado eliminado.');
    }
}
