@extends('layouts.app')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-6">
  <div class="max-w-7xl mx-auto">

    {{-- ======= Encabezado ======= --}}
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Dashboard</h1>
      <p class="text-gray-500 dark:text-gray-400">Resumen del mes actual (vs. mismo mes año pasado)</p>
    </div>

    {{-- ======= Tarjetas superiores (3) ======= --}}
    <div class="grid grid-cols-12 gap-6 mb-6">

      {{-- Facturación --}}
      @php
        $f = $facturas;
        $fColor = is_null($f['delta']) ? 'text-gray-500 bg-gray-500/10' : ($f['delta']>=0 ? 'text-green-700 bg-green-500/20' : 'text-rose-700 bg-rose-500/20');
        $fDelta = is_null($f['delta']) ? '—' : ( ($f['delta']>=0?'+':'').$f['delta'].'%' );
        $fTitle = $f['esMonto'] ? 'Monto facturado' : 'Facturas (conteo)';
        $money = fn($n) => '$'.number_format($n, 2);
      @endphp
      <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-gray-800 shadow-xs rounded-xl">
        <div class="px-5 pt-5">
          <header class="flex justify-between items-start mb-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">{{ $fTitle }}</h2>
            <div class="relative inline-flex">
              <button class="rounded-full text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" aria-haspopup="true" aria-expanded="false">
                <span class="sr-only">Menu</span>
                <svg class="w-8 h-8 fill-current" viewBox="0 0 32 32"><circle cx="16" cy="16" r="2"/><circle cx="10" cy="16" r="2"/><circle cx="22" cy="16" r="2"/></svg>
              </button>
            </div>
          </header>
          <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-1">Este mes</div>
          <div class="flex items-start">
            <div class="text-3xl font-bold text-gray-800 dark:text-gray-100 mr-2">
              {{ $f['esMonto'] ? $money($f['actual']) : number_format($f['actual']) }}
            </div>
            <div class="text-sm font-medium px-1.5 rounded-full {{ $fColor }}">{{ $fDelta }}</div>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Anterior: {{ $f['esMonto'] ? $money($f['previo']) : number_format($f['previo']) }}
          </div>
        </div>
        <div class="grow max-sm:max-h-[128px] xl:max-h-[128px]">
          <canvas id="sparkFacturas" height="160"></canvas>
        </div>
      </div>

      {{-- Complementos de pago (monto cobrado) --}}
      @php
        $c = $complementos;
        $cColor = is_null($c['delta']) ? 'text-gray-500 bg-gray-500/10' : ($c['delta']>=0 ? 'text-green-700 bg-green-500/20' : 'text-rose-700 bg-rose-500/20');
        $cDelta = is_null($c['delta']) ? '—' : ( ($c['delta']>=0?'+':'').$c['delta'].'%' );
      @endphp
      <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-gray-800 shadow-xs rounded-xl">
        <div class="px-5 pt-5">
          <header class="flex justify-between items-start mb-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Monto en complementos</h2>
            <div class="relative inline-flex">
              <button class="rounded-full text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" aria-haspopup="true" aria-expanded="false">
                <span class="sr-only">Menu</span>
                <svg class="w-8 h-8 fill-current" viewBox="0 0 32 32"><circle cx="16" cy="16" r="2"/><circle cx="10" cy="16" r="2"/><circle cx="22" cy="16" r="2"/></svg>
              </button>
            </div>
          </header>
          <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-1">Este mes</div>
          <div class="flex items-start">
            <div class="text-3xl font-bold text-gray-800 dark:text-gray-100 mr-2">{{ $money($c['actual']) }}</div>
            <div class="text-sm font-medium px-1.5 rounded-full {{ $cColor }}">{{ $cDelta }}</div>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Anterior: {{ $money($c['previo']) }}</div>
        </div>
        <div class="grow max-sm:max-h-[128px] xl:max-h-[128px]">
          <canvas id="sparkComplementos" height="160"></canvas>
        </div>
      </div>

      {{-- Nóminas --}}
      @php
        $n = $nominas;
        $nColor = is_null($n['delta']) ? 'text-gray-500 bg-gray-500/10' : ($n['delta']>=0 ? 'text-green-700 bg-green-500/20' : 'text-rose-700 bg-rose-500/20');
        $nDelta = is_null($n['delta']) ? '—' : ( ($n['delta']>=0?'+':'').$n['delta'].'%' );
      @endphp
      <div class="flex flex-col col-span-full sm:col-span-6 xl:col-span-4 bg-white dark:bg-gray-800 shadow-xs rounded-xl">
        <div class="px-5 pt-5">
          <header class="flex justify-between items-start mb-2">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Monto de nóminas</h2>
            <div class="relative inline-flex">
              <button class="rounded-full text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400" aria-haspopup="true" aria-expanded="false">
                <span class="sr-only">Menu</span>
                <svg class="w-8 h-8 fill-current" viewBox="0 0 32 32"><circle cx="16" cy="16" r="2"/><circle cx="10" cy="16" r="2"/><circle cx="22" cy="16" r="2"/></svg>
              </button>
            </div>
          </header>
          <div class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase mb-1">Este mes</div>
          <div class="flex items-start">
            <div class="text-3xl font-bold text-gray-800 dark:text-gray-100 mr-2">{{ $money($n['actual']) }}</div>
            <div class="text-sm font-medium px-1.5 rounded-full {{ $nColor }}">{{ $nDelta }}</div>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Anterior: {{ $money($n['previo']) }}</div>
        </div>
        <div class="grow max-sm:max-h-[128px] xl:max-h-[128px]">
          <canvas id="sparkNominas" height="160"></canvas>
        </div>
      </div>

    </div>

    {{-- ======= Tarjetas inferiores: listas + checklist ======= --}}
    <div class="grid grid-cols-12 gap-6">
      {{-- Últimas 10 Facturas --}}
      <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-xs rounded-xl">
        <div class="px-5 pt-5 pb-3">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Últimas 10 facturas</h2>
        </div>
        <div class="px-5 pb-5">
          <ul class="divide-y divide-gray-200 dark:divide-gray-700/60">
            @forelse($ultimasFacturas as $fac)
              <li class="py-3 flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-gray-800 dark:text-gray-100">UUID: {{ $fac->uuid ?? '—' }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Fecha: {{ \Carbon\Carbon::parse($fac->fecha ?? $fac->created_at)->format('Y-m-d') }}</div>
                </div>
                <div class="text-sm text-gray-700 dark:text-gray-300">
                  {{-- Si tienes columna total, muéstrala; si no, badge de "CFDI" --}}
                  @if(isset($facturas['esMonto']) && $facturas['esMonto'])
                    @php
                      // Intento leer el total si lo tienes en la consulta (aquí no viene), así que lo omitimos
                    @endphp
                    <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200">CFDI</span>
                  @else
                    <span class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-700/50 text-gray-700 dark:text-gray-200">CFDI</span>
                  @endif
                </div>
              </li>
            @empty
              <li class="py-6 text-sm text-gray-500 dark:text-gray-400">Sin facturas aún.</li>
            @endforelse
          </ul>
        </div>
      </div>

      {{-- Últimos 10 Complementos --}}
      <div class="col-span-full xl:col-span-6 bg-white dark:bg-gray-800 shadow-xs rounded-xl">
        <div class="px-5 pt-5 pb-3">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Últimos 10 complementos</h2>
        </div>
        <div class="px-5 pb-5">
          <ul class="divide-y divide-gray-200 dark:divide-gray-700/60">
            @forelse($ultimosComplementos as $comp)
              @php $monto = (float) ($montosComplementos[$comp->id] ?? 0); @endphp
              <li class="py-3 flex items-center justify-between">
                <div>
                  <div class="text-sm font-medium text-gray-800 dark:text-gray-100">UUID: {{ $comp->uuid ?? '—' }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Fecha pago: {{ \Carbon\Carbon::parse($comp->fecha ?? $comp->created_at)->format('Y-m-d') }}</div>
                </div>
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ '$'.number_format($monto,2) }}</div>
              </li>
            @empty
              <li class="py-6 text-sm text-gray-500 dark:text-gray-400">Sin complementos aún.</li>
            @endforelse
          </ul>
        </div>
      </div>

      {{-- Checklist --}}
      <div class="col-span-full bg-white dark:bg-gray-800 shadow-xs rounded-xl">
        <div class="px-5 pt-5 pb-3">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Checklist de configuración</h2>
          <p class="text-sm text-gray-500 dark:text-gray-400">Requisitos mínimos para poder timbrar sin problemas</p>
        </div>
        <div class="px-5 pb-5">
          @php
            $items = [
              'Datos del RFC completos (RFC, Razón Social, CP)' => !in_array('Completar datos del RFC (RFC, Razón Social, CP).', $pendientes),
              'CSD activo para timbrar'                         => !in_array('Activar un CSD válido para timbrar.', $pendientes),
              'Al menos un cliente'                             => !in_array('Cargar al menos un cliente.', $pendientes),
              'Folios configurados'                             => !in_array('Configurar folios de facturación.', $pendientes),
            ];
          @endphp
          <ul class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($items as $label => $ok)
              <li class="flex items-center gap-2 p-3 rounded-lg border
                         {{ $ok ? 'border-green-200 bg-green-50 dark:border-green-900/40 dark:bg-green-900/10' : 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-900/10' }}">
                @if($ok)
                  <svg class="w-5 h-5 text-green-600" viewBox="0 0 20 20" fill="currentColor"><path d="M16.707 5.293a1 1 0 00-1.414-1.414L8 11.172l-2.293-2.293A1 1 0 104.293 10.293l3 3a1 1 0 001.414 0l8-8z"/></svg>
                @else
                  <svg class="w-5 h-5 text-rose-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3-9a1 1 0 10-2 0v4a1 1 0 102 0V9zm-4 0a1 1 0 10-2 0v4a1 1 0 102 0V9z" clip-rule="evenodd"/></svg>
                @endif
                <span class="text-sm font-medium {{ $ok ? 'text-green-800 dark:text-green-200' : 'text-rose-800 dark:text-rose-200' }}">{{ $label }}</span>
              </li>
            @endforeach
          </ul>

          @if(empty($pendientes))
            <div class="mt-4 text-sm text-green-700 dark:text-green-300">¡Todo listo! ✅</div>
          @else
            <div class="mt-4">
              <div class="text-sm font-medium text-rose-700 dark:text-rose-300 mb-1">Pendientes:</div>
              <ul class="list-disc list-inside text-sm text-rose-700 dark:text-rose-300">
                @foreach($pendientes as $p)
                  <li>{{ $p }}</li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>
      </div>
    </div>

  </div>
</div>

{{-- ======= Sparkline charts (opcional, si Chart.js está disponible) ======= --}}
<script>
  (function () {
    if (typeof window.Chart === 'undefined') return;

    const mkSpark = (id, data) => {
      const el = document.getElementById(id);
      if (!el) return;
      const ctx = el.getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.map((_, i) => i + 1),
          datasets: [{
            data: data,
            fill: true,
            borderWidth: 2,
            tension: .35
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false }, tooltip: { enabled: false } },
          scales: { x: { display: false }, y: { display: false } },
          elements: { point: { radius: 0 } }
        }
      });
    };

    mkSpark('sparkFacturas', @json($facturas['serie']));
    mkSpark('sparkComplementos', @json($complementos['serie']));
    mkSpark('sparkNominas', @json($nominas['serie']));
  })();
</script>
@endsection
