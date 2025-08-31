<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\RfcUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PerfilRfcController extends Controller
{
    public function edit(Request $request)
    {
        $rfcStr = session('rfc_seleccionado');
        abort_unless($rfcStr, 422, 'Selecciona un RFC activo desde el menú.');

        $rfc = RfcUsuario::where('rfc', $rfcStr)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('configuracion/perfil/edit', compact('rfc'));
    }

    public function update(Request $request)
    {
        $rfcStr = session('rfc_seleccionado');
        abort_unless($rfcStr, 422, 'Selecciona un RFC activo desde el menú.');

        $rfc = RfcUsuario::where('rfc', $rfcStr)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        // Validación: solo campos de perfil (NO tocamos el RFC en sí)
        $data = $request->validate([
            'razon_social'   => ['required','string','max:255'],
            'regimen_fiscal' => ['nullable','string','max:3'],
            'cp_expedicion'  => ['nullable','digits:5'],

            'calle'          => ['nullable','string','max:255'],
            'no_ext'         => ['nullable','string','max:50'],
            'no_int'         => ['nullable','string','max:50'],
            'colonia'        => ['nullable','string','max:255'],
            'municipio'      => ['nullable','string','max:255'],
            'localidad'      => ['nullable','string','max:255'],
            'estado'         => ['nullable','string','max:255'],
            'codigo_postal'  => ['nullable','digits:5'],

            'email'          => ['nullable','email','max:255'],
            'telefono'       => ['nullable','string','max:50'],

            'logo'           => ['nullable','image','max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store("rfcs/{$rfc->rfc}/branding", 'public');
            $data['logo_path'] = $path;
        }

        $rfc->update($data);

        return back()->with('ok', 'Perfil del RFC actualizado.');
    }
}
