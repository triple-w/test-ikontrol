<?php

namespace App\Services\Timbres;

use App\Exceptions\SinTimbresException;
use App\Models\TimbreCuenta;
use App\Models\TimbreMovimiento;
use Illuminate\Support\Facades\DB;

class TimbreService
{
    /**
     * Asigna timbres a un RFC (solo admin).
     */
    public function asignar(int $rfcId, int $cantidad, int $userId, ?string $referencia = null): TimbreCuenta
    {
        return DB::transaction(function () use ($rfcId, $cantidad, $userId, $referencia) {
            $cuenta = TimbreCuenta::firstOrCreate(['rfc_id' => $rfcId], [
                'asignados_total' => 0,
                'consumidos_total' => 0,
            ]);

            $cuenta->increment('asignados_total', $cantidad);

            TimbreMovimiento::create([
                'rfc_id'     => $rfcId,
                'user_id'    => $userId,
                'tipo'       => 'asignacion',
                'cantidad'   => $cantidad,
                'referencia' => $referencia,
            ]);

            return $cuenta->refresh();
        });
    }

    /**
     * Consume timbres (timbrado/cancelacion/verificacion_cancelacion).
     */
    public function consumir(int $rfcId, string $tipo, int $userId, ?string $referencia = null, int $cantidad = 1): TimbreCuenta
    {
        if (!in_array($tipo, ['timbrado','cancelacion','verificacion_cancelacion'], true)) {
            throw new \InvalidArgumentException('Tipo de consumo inválido.');
        }

        return DB::transaction(function () use ($rfcId, $tipo, $userId, $referencia, $cantidad) {
            $cuenta = TimbreCuenta::lockForUpdate()->firstOrCreate(['rfc_id' => $rfcId], [
                'asignados_total' => 0,
                'consumidos_total' => 0,
            ]);

            if (($cuenta->asignados_total - $cuenta->consumidos_total) < $cantidad) {
                throw new SinTimbresException();
            }

            $cuenta->increment('consumidos_total', $cantidad);

            TimbreMovimiento::create([
                'rfc_id'     => $rfcId,
                'user_id'    => $userId,
                'tipo'       => $tipo,
                'cantidad'   => $cantidad,
                'referencia' => $referencia,
            ]);

            return $cuenta->refresh();
        });
    }

    /**
     * Devuelve timbres disponibles para el RFC.
     */
    public function disponibles(int $rfcId): int
    {
        $cuenta = TimbreCuenta::firstOrCreate(['rfc_id' => $rfcId], [
            'asignados_total' => 0,
            'consumidos_total' => 0,
        ]);
        return max(0, $cuenta->asignados_total - $cuenta->consumidos_total);
    }
}
