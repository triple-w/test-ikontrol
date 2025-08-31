<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RfcUsuario;

class RfcController extends Controller
{
    public function cambiar(Request $request)
    {
        $rfc = RfcUsuario::where('id', $request->rfc_id)
                         ->where('user_id', auth()->id())
                         ->firstOrFail();

        session(['rfc_seleccionado' => $rfc->rfc]);

        return redirect()->back();
    }
}
