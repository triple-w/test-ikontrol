@extends('layouts.app')

@section('title','Previsualización de factura')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6 space-y-6">

  {{-- Header --}}
  <div class="flex items-start justify-between">
    <div>
      <h1 class="text-2xl font-bold">Previsualización</h1>
      <div class="text-sm text-gray-500">RFC emisor: <span class="font-medium">{{ $emisor_rfc }}</span></div>
    </div>
    <div class="text-right">
      <div class="text-xs uppercase tracking-wide text-gray-500">Tipo</div>
      <div class="font-semibold">
        {{ $comprobante['tipo_comprobante']=='I' ? 'Ingreso' : 'Egreso' }}
      </div>
      <div class="text-xs uppercase tracking-wide text-gray-500 mt-1">Serie / Folio</div>
      <div class="font-semibold">
        {{ $comprobante['serie'] }}-{{ $comprobante['folio'] }}
      </div>
    </div>
  </div>

  {{-- Emisor / Receptor (Cliente) --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
      <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Emisor</div>
      <div class="font-semibold">{{ $emisor_rfc }}</div>
      {{-- Si quieres, aquí puedes pintar razón social del emisor desde sesión/perfil --}}
    </div>

    <div>
      <div class="text-xs uppercase tracking-wide text-gray-500 mb-1">Receptor (Cliente)</div>
      <div class="font-semibold">{{ $cliente->razon_social ?? $cliente->nombre ?? '—' }}</div>
      <div class="text-sm text-gray-700 dark:text-gray-300">RFC: {{ $cliente->rfc }}</div>
      <div class="text-sm text-gray-700 dark:text-gray-300">
        {{ trim(($cliente->calle ?? '').' '.($cliente->no_ext ?? '').' '.($cliente->no_int ? 'Int '.$cliente->no_int : '')) }}
      </div>
      <div class="text-sm text-gray-700 dark:text-gray-300">
        {{ trim(($cliente->colonia ?? '').', '.($cliente->localidad ?? '').', '.($cliente->estado ?? '')) }} {{ $cliente->codigo_postal ?? '' }}
      </div>
      @if (!empty($cliente->pais))
        <div class="text-sm text-gray-700 dark:text-gray-300">País: {{ $cliente->pais }}</div>
      @endif
      @if (!empty($cliente->email))
        <div class="text-sm text-gray-700 dark:text-gray-300">Email: {{ $cliente->email }}</div>
      @endif
    </div>
  </div>

  {{-- Datos del comprobante --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
      <div>
        <div class="text-gray-500">Fecha</div>
        <div class="font-semibold">{{ \Illuminate\Support\Carbon::parse($comprobante['fecha'])->format('Y-m-d H:i') }}</div>
      </div>
      <div>
        <div class="text-gray-500">Método de pago</div>
        <div class="font-semibold">{{ $comprobante['metodo_pago'] }}</div>
      </div>
      <div>
        <div class="text-gray-500">Forma de pago</div>
        <div class="font-semibold">{{ $comprobante['forma_pago'] }}</div>
      </div>
    </div>

    {{-- Documentos Relacionados (si hay) --}}
    @if (!empty($relacionados))
      <div class="mt-4">
        <div class="text-gray-500 text-sm mb-1">Documentos Relacionados</div>
        <div class="border rounded-lg divide-y">
          @foreach ($relacionados as $rel)
            @php
              // Estructura esperada:
              // $rel = ['tipo_relacion'=>'01', 'uuids'=>['UUID1','UUID2',...]]
              $tipoRel = $rel['tipo_relacion'] ?? $rel['tipo'] ?? '';
              $uuids   = $rel['uuids'] ?? $rel['cfdis'] ?? [];
              if (!is_array($uuids)) $uuids = [];
            @endphp
            <div class="p-3 text-sm">
              <div class="font-medium">Tipo relación: {{ $tipoRel }}</div>
              @if (count($uuids))
                <ul class="list-disc pl-5 mt-1">
                  @foreach ($uuids as $u)
                    <li class="break-all">{{ $u }}</li>
                  @endforeach
                </ul>
              @else
                <div class="text-gray-500">Sin UUIDs especificados</div>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    @endif

    {{-- Comentarios en PDF (si hay) --}}
    @if (!empty($comentarios_pdf))
      <div class="mt-4">
        <div class="text-gray-500 text-sm mb-1">Comentarios (PDF)</div>
        <div class="p-3 border rounded-lg bg-gray-50 dark:bg-gray-900/40 text-sm whitespace-pre-wrap">
          {{ $comentarios_pdf }}
        </div>
      </div>
    @endif
  </div>

  {{-- Conceptos --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 overflow-x-auto">
    <table class="table-auto w-full text-sm">
      <thead class="text-xs uppercase text-gray-500 border-b">
        <tr>
          <th class="px-2 py-2 text-left w-32">Clave</th>
          <th class="px-2 py-2 text-left">Descripción</th>
          <th class="px-2 py-2 text-right w-20">Cant.</th>
          <th class="px-2 py-2 text-right w-24">Precio</th>
          <th class="px-2 py-2 text-right w-24">Desc.</th>
          <th class="px-2 py-2 text-right w-24">Importe</th>
        </tr>
      </thead>
      <tbody>
      @foreach(($comprobante['conceptos'] ?? []) as $c)
        @php
          $sub = (float)$c['cantidad'] * (float)$c['precio'];
          $des = (float)($c['descuento'] ?? 0);
          $base = max($sub - $des, 0);
          $imp  = 0.0;
          foreach (($c['impuestos'] ?? []) as $i) {
            if (($i['factor'] ?? '') === 'Exento') continue;
            $tasa = (float)($i['tasa'] ?? 0)/100;
            $m = $base * $tasa;
            $imp += (($i['tipo'] ?? 'T')==='R') ? -$m : $m;
          }
          $importe = $base + $imp;
        @endphp
        <tr class="border-b border-gray-100">
          <td class="px-2 py-2 align-top">
            <div>{{ $c['clave_prod_serv'] ?? '' }}</div>
            <div class="text-xs text-gray-500">{{ $c['clave_unidad'] ?? '' }}</div>
          </td>
          <td class="px-2 py-2 align-top">
            <div class="font-medium">{{ $c['descripcion'] }}</div>
            <div class="text-xs text-gray-500">Unidad: {{ $c['unidad'] ?? '' }}</div>
            @if (!empty($c['impuestos']))
              <div class="text-xs text-gray-500 mt-1">
                @php
                  $parts = [];
                  foreach ($c['impuestos'] as $i) {
                    $lbl = ($i['tipo'] ?? 'T')==='R' ? 'Ret.' : 'Tras.';
                    $impu= $i['impuesto'] ?? 'IMP';
                    $fac = $i['factor'] ?? '';
                    $tas = isset($i['tasa']) ? rtrim(rtrim(number_format((float)$i['tasa'],4,'.',''),'0'),'.') : '';
                    $parts[] = "{$lbl} {$impu} {$fac} {$tas}%";
                  }
                @endphp
                {{ implode(' • ', $parts) }}
              </div>
            @endif
          </td>
          <td class="px-2 py-2 text-right align-top">{{ number_format((float)($c['cantidad'] ?? 0),3) }}</td>
          <td class="px-2 py-2 text-right align-top">{{ number_format((float)($c['precio'] ?? 0),2) }}</td>
          <td class="px-2 py-2 text-right align-top">{{ number_format($des,2) }}</td>
          <td class="px-2 py-2 text-right align-top">{{ number_format($importe,2) }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  {{-- Totales + Botonera --}}
  <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
    <div class="flex items-center gap-2">
      <button type="button" class="btn bg-gray-100 hover:opacity-90" onclick="history.back()">← Regresar</button>

      {{-- Guardar --}}
      <form method="POST" action="{{ route('facturas.guardar') }}">
        @csrf
        <input type="hidden" name="payload" value="{{ e(json_encode($comprobante)) }}">
        <button class="btn bg-gray-100 hover:opacity-90">Guardar borrador</button>
      </form>

      {{-- Timbrar --}}
      <form method="POST" action="{{ route('facturas.timbrar') }}">
        @csrf
        <input type="hidden" name="payload" value="{{ e(json_encode($comprobante)) }}">
        <button class="btn bg-violet-600 hover:bg-violet-700 text-white">Timbrar</button>
      </form>
    </div>

    <div class="w-full sm:w-auto">
      <div class="w-full sm:min-w-[320px] bg-white dark:bg-gray-800 rounded-xl shadow p-4 text-sm space-y-1">
        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ number_format($totales['subtotal'],2) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Descuento</span><span>{{ number_format($totales['descuento'],2) }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Impuestos</span><span>{{ number_format($totales['impuestos'],2) }}</span></div>
        <div class="border-t pt-2 mt-2 flex justify-between font-semibold text-gray-700">
          <span>Total</span><span>{{ number_format($totales['total'],2) }}</span>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
