<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RfcUsuario;
use App\Models\TimbreCuenta;
use App\Models\TimbreMovimiento;
use App\Services\Timbres\TimbreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimbresController extends Controller
{
    public function __construct()
    {
        // Seguridad simple: solo admin
        $this->middleware(function ($request, $next) {
        if (!auth()->check() || !method_exists(auth()->user(), 'isAdmin') || !auth()->user()->isAdmin()) {
            abort(403);
        }
        return $next($request);
    });
    }

    public function index(TimbreService $svc)
    {
        $rfcs = RfcUsuario::orderBy('rfc')->get();

        // Join cuentas
        $cuentas = TimbreCuenta::with('rfc')->get()->keyBy('rfc_id');

        return view('admin/timbres/index', compact('rfcs','cuentas'));
    }

    public function store(Request $request, TimbreService $svc)
    {
        $data = $request->validate([
            'rfc_id'   => ['required','exists:rfc_usuarios,id'],
            'cantidad' => ['required','integer','min:1'],
            'referencia' => ['nullable','string','max:255'],
        ]);

        $svc->asignar($data['rfc_id'], (int)$data['cantidad'], Auth::id(), $data['referencia'] ?? null);

        return back()->with('ok', 'Timbres asignados correctamente.');
    }

    public function history(Request $request)
    {
        $rfcId = $request->query('rfc_id');

        $query = TimbreMovimiento::with(['rfc','usuario'])->latest();
        if ($rfcId) {
            $query->where('rfc_id', $rfcId);
        }

        $movs = $query->paginate(50);
        $rfcs = RfcUsuario::orderBy('rfc')->get();

        return view('admin/timbres/history', compact('movs','rfcs','rfcId'));
    }
}
