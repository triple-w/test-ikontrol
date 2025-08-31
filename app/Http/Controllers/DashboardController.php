<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ================= RFC activo =================
        $rfcId = $this->obtenerRfcActivo($user->id);
        if (!$rfcId) {
            // Si no hay RFC, manda a configurar perfil
            return redirect()->route('perfil.edit')->with('warning', 'Configura un RFC para ver tu dashboard.');
        }

        // ================ Fechas (mes actual vs mismo mes año pasado) ================
        $ahora         = Carbon::now();
        $iniMesActual  = $ahora->copy()->startOfMonth();
        $finMesActual  = $ahora->copy()->endOfMonth();
        $iniMesPasado  = $iniMesActual->copy()->subYear();
        $finMesPasado  = $finMesActual->copy()->subYear();
        $diasMes       = (int) $finMesActual->format('d');

        // Detectar columna de fecha en facturas
        $colFechaFacturas  = Schema::hasColumn('facturas', 'fecha_factura') ? 'fecha_factura' : 'fecha';
        $tieneEstatusFact  = Schema::hasColumn('facturas', 'estatus');
        $tieneColTotalFact = Schema::hasColumn('facturas', 'total'); // si existe, se usa directo

        // ================= FACTURAS (montos) =================
        // Mes actual
        $qFA = DB::table('facturas')
            ->where('rfc_usuario_id', $rfcId)
            ->whereBetween($colFechaFacturas, [$iniMesActual, $finMesActual]);

        if ($tieneEstatusFact) $qFA->where('estatus', 'timbrada');

        $facturasActualRows = $qFA->get(['id', $colFechaFacturas, $tieneColTotalFact ? 'total' : 'xml']);

        // Mismo mes año pasado
        $qFP = DB::table('facturas')
            ->where('rfc_usuario_id', $rfcId)
            ->whereBetween($colFechaFacturas, [$iniMesPasado, $finMesPasado]);

        if ($tieneEstatusFact) $qFP->where('estatus', 'timbrada');

        $facturasPrevRows = $qFP->get(['id', $colFechaFacturas, $tieneColTotalFact ? 'total' : 'xml']);

        // Sumas de montos
        $montoMesActual = 0.0;
        foreach ($facturasActualRows as $r) {
            $montoMesActual += $tieneColTotalFact ? (float) $r->total : $this->totalDesdeXml($r->xml);
        }

        $montoMesPasado = 0.0;
        foreach ($facturasPrevRows as $r) {
            $montoMesPasado += $tieneColTotalFact ? (float) $r->total : $this->totalDesdeXml($r->xml);
        }

        // Serie por día (sparkline) con montos
        $serieFacturas = array_fill(1, $diasMes, 0.0);
        foreach ($facturasActualRows as $r) {
            $dia = (int) Carbon::parse($r->{$colFechaFacturas})->format('d');
            $monto = $tieneColTotalFact ? (float) $r->total : $this->totalDesdeXml($r->xml);
            $serieFacturas[$dia] += $monto;
        }

        // ================= COMPLEMENTOS (suma de pagos) =================
        $complementosMontoActual = DB::table('complementos')
            ->join('complementos_pagos', 'complementos_pagos.users_complementos_id', '=', 'complementos.id')
            ->where('complementos.rfc_usuario_id', $rfcId)
            ->whereBetween('complementos_pagos.fecha_pago', [$iniMesActual, $finMesActual])
            ->sum('complementos_pagos.monto_pago');

        $complementosMontoPrevio = DB::table('complementos')
            ->join('complementos_pagos', 'complementos_pagos.users_complementos_id', '=', 'complementos.id')
            ->where('complementos.rfc_usuario_id', $rfcId)
            ->whereBetween('complementos_pagos.fecha_pago', [$iniMesPasado, $finMesPasado])
            ->sum('complementos_pagos.monto_pago');

        $serieComplementos = array_fill(1, $diasMes, 0.0);
        $complePorDia = DB::table('complementos')
            ->join('complementos_pagos', 'complementos_pagos.users_complementos_id', '=', 'complementos.id')
            ->selectRaw('DATE(complementos_pagos.fecha_pago) d, SUM(complementos_pagos.monto_pago) t')
            ->where('complementos.rfc_usuario_id', $rfcId)
            ->whereBetween('complementos_pagos.fecha_pago', [$iniMesActual, $finMesActual])
            ->groupBy('d')
            ->pluck('t', 'd')
            ->toArray();
        foreach ($complePorDia as $fecha => $valor) {
            $serieComplementos[(int) Carbon::parse($fecha)->format('d')] = (float) $valor;
        }

        // ================= NÓMINAS (suma de total) =================
        $tieneTotalNomina = Schema::hasColumn('nominas', 'total'); // según tu estructura
        $colNomFecha      = 'fecha';

        $nominasMontoActual = DB::table('nominas')
            ->where('rfc_usuario_id', $rfcId)
            ->whereBetween($colNomFecha, [$iniMesActual, $finMesActual])
            ->sum($tieneTotalNomina ? 'total' : DB::raw('0')); // si no existe, quedará 0

        $nominasMontoPrevio = DB::table('nominas')
            ->where('rfc_usuario_id', $rfcId)
            ->whereBetween($colNomFecha, [$iniMesPasado, $finMesPasado])
            ->sum($tieneTotalNomina ? 'total' : DB::raw('0'));

        $serieNominas = array_fill(1, $diasMes, 0.0);
        if ($tieneTotalNomina) {
            $nominasPorDia = DB::table('nominas')
                ->selectRaw('DATE('.$colNomFecha.') d, SUM(total) t')
                ->where('rfc_usuario_id', $rfcId)
                ->whereBetween($colNomFecha, [$iniMesActual, $finMesActual])
                ->groupBy('d')
                ->pluck('t', 'd')
                ->toArray();
            foreach ($nominasPorDia as $fecha => $valor) {
                $serieNominas[(int) Carbon::parse($fecha)->format('d')] = (float) $valor;
            }
        }

        // ================= Variaciones % =================
        $delta = function (float $actual, float $previo) {
            if ($previo == 0.0) return null;
            return (int) round((($actual - $previo) / $previo) * 100);
        };

        // ================= Listas (últimos 10) =================
        $ultimasFacturas = DB::table('facturas')
            ->where('rfc_usuario_id', $rfcId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id','uuid',$colFechaFacturas.' as fecha','created_at']);

        $ultimosComplementos = DB::table('complementos')
            ->where('rfc_usuario_id', $rfcId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id','uuid','fecha','created_at']);

        $montosComplementos = DB::table('complementos_pagos')
            ->selectRaw('users_complementos_id as idc, SUM(monto_pago) as monto')
            ->groupBy('idc')
            ->pluck('monto', 'idc');

        // ================= Checklist =================
        $pendientes = [];

        // Perfil básico del RFC
        if (Schema::hasTable('rfc_usuarios')) {
            $perfil = DB::table('rfc_usuarios')->where('id', $rfcId)->first();
            if (!$perfil || empty($perfil->rfc) || empty($perfil->razon_social) || empty($perfil->codigo_postal)) {
                $pendientes[] = 'Completar datos del RFC (RFC, Razón Social, CP).';
            }
        }

        // CSD activo (nota: FK es rfc_usuario_id)
        if (Schema::hasTable('rfc_csds')) {
            $csdOk = DB::table('rfc_csds')
                ->where('rfc_usuario_id', $rfcId)
                ->where('activo', 1)
                ->exists();
            if (!$csdOk) $pendientes[] = 'Activar un CSD válido para timbrar.';
        }

        // Clientes
        if (Schema::hasTable('clientes')) {
            $clientesQuery = DB::table('clientes');
            if (Schema::hasColumn('clientes', 'rfc_usuario_id')) {
                $clientesQuery->where('rfc_usuario_id', $rfcId);
            } else {
                foreach (['users_id', 'user_id'] as $col) {
                    if (Schema::hasColumn('clientes', $col)) {
                        $clientesQuery->where($col, $user->id);
                        break;
                    }
                }
            }
            if (!$clientesQuery->exists()) $pendientes[] = 'Cargar al menos un cliente.';
        }

        // Folios
        if (Schema::hasTable('folios')) {
            $foliosQuery = DB::table('folios');
            if (Schema::hasColumn('folios', 'rfc_usuario_id')) {
                $foliosQuery->where('rfc_usuario_id', $rfcId);
            } else {
                foreach (['users_id', 'user_id'] as $col) {
                    if (Schema::hasColumn('folios', $col)) {
                        $foliosQuery->where($col, $user->id);
                        break;
                    }
                }
            }
            if (!$foliosQuery->exists()) $pendientes[] = 'Configurar folios de facturación.';
        }

        // ================= Render =================
        return view('dashboard.index', [
            'rfcId' => $rfcId,

            'facturas' => [
                'actual' => (float) $montoMesActual,
                'previo' => (float) $montoMesPasado,
                'delta'  => $delta($montoMesActual, $montoMesPasado),
                'serie'  => array_values($serieFacturas),
                'esMonto'=> true,
            ],

            'complementos' => [
                'actual' => (float) $complementosMontoActual,
                'previo' => (float) $complementosMontoPrevio,
                'delta'  => $delta($complementosMontoActual, $complementosMontoPrevio),
                'serie'  => array_values($serieComplementos),
            ],

            'nominas' => [
                'actual' => (float) $nominasMontoActual,
                'previo' => (float) $nominasMontoPrevio,
                'delta'  => $delta($nominasMontoActual, $nominasMontoPrevio),
                'serie'  => array_values($serieNominas),
            ],

            'ultimasFacturas'     => $ultimasFacturas,
            'ultimosComplementos' => $ultimosComplementos,
            'montosComplementos'  => $montosComplementos,

            'pendientes' => $pendientes,
        ]);
    }

    /**
     * Devuelve el id del RFC activo para el usuario.
     * - Primero usa sesión ('rfc_activo_id')
     * - Luego busca en rfc_usuarios (primero activo=1 si existe, luego el primero)
     * - Guarda en sesión para siguientes requests
     */
    private function obtenerRfcActivo(int $userId): ?int
    {
        if (session()->has('rfc_activo_id')) {
            return (int) session('rfc_activo_id');
        }

        if (!Schema::hasTable('rfc_usuarios')) return null;

        // Detectar nombre de columna FK al usuario
        $colUserRU = null;
        foreach (['user_id', 'users_id', 'usuario_id'] as $c) {
            if (Schema::hasColumn('rfc_usuarios', $c)) {
                $colUserRU = $c;
                break;
            }
        }

        $ru = DB::table('rfc_usuarios');
        if ($colUserRU) $ru->where($colUserRU, $userId);

        // Si existe columna 'activo', preferir el activo
        if (Schema::hasColumn('rfc_usuarios', 'activo')) {
            $ruActivo = clone $ru;
            $id = $ruActivo->where('activo', 1)->orderBy('id')->value('id');
            if ($id) {
                session(['rfc_activo_id' => (int) $id]);
                return (int) $id;
            }
        }

        // Fallback: el primero
        $id = $ru->orderBy('id')->value('id');
        if ($id) session(['rfc_activo_id' => (int) $id]);

        return $id ? (int) $id : null;
    }

    /**
     * Extrae el atributo Total del CFDI 4.0 desde el XML.
     * Si no puede, devuelve 0.0
     */
    private function totalDesdeXml(?string $xml): float
    {
        if (!$xml) return 0.0;

        // Rápido: regex sobre el atributo Total="..."
        if (preg_match('/\bTotal="([\d]+(?:\.[\d]+)?)"/', $xml, $m)) {
            return (float) $m[1];
        }

        // Fallback: SimpleXML
        try {
            libxml_use_internal_errors(true);
            $sx = simplexml_load_string($xml);
            if ($sx !== false) {
                $attrs = $sx->attributes();
                if ($attrs && (isset($attrs['Total']) || isset($attrs['total']))) {
                    return (float) ($attrs['Total'] ?? $attrs['total']);
                }
            }
        } catch (\Throwable $e) {
            // ignorar
        }
        return 0.0;
    }
}
