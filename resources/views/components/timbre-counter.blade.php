@php
  use App\Models\RfcUsuario;

  $rfcs = Auth::user()->rfcs ?? collect();
  $rfcActivoStr = session('rfc_seleccionado', $rfcs->first()?->rfc);
  $rfcActivo = $rfcs->firstWhere('rfc', $rfcActivoStr);

  $disponibles = null;
  if ($rfcActivo) {
      try {
          if (class_exists(\App\Services\Timbres\TimbreService::class)) {
              $disponibles = app(\App\Services\Timbres\TimbreService::class)->disponibles($rfcActivo->id);
          }
      } catch (\Throwable $e) {
          // ignoramos y caemos al fallback
      }

      if (!is_numeric($disponibles)) {
          // Fallback directo al modelo (ajusta el namespace si difiere)
          try {
              $cuenta = \App\Models\TimbreCuenta::where('rfc_id', $rfcActivo->id)->first();
              $disponibles = $cuenta ? max(0, (int)$cuenta->asignados_total - (int)$cuenta->consumidos_total) : 0;
          } catch (\Throwable $e) {
              $disponibles = 0;
          }
      }
  } else {
      $disponibles = 0;
  }

  $isLow = $disponibles <= 10;
@endphp

<div class="flex items-center gap-2">
  <span class="text-[11px] text-gray-500 dark:text-gray-400">Timbres:</span>
  <span
    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold
          {{ $isLow ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200'
                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-100' }}"
    title="{{ $isLow ? 'Pocos timbres (≤10)' : 'Timbres disponibles' }}"
  >
    {{ $disponibles }}
  </span>
</div>
