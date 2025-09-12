@extends('layouts.app')

@section('title','Previsualización de factura')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Previsualización</h1>
    <div class="text-sm text-gray-500">RFC emisor: <span class="font-medium">{{ $emisor_rfc }}</span></div>
  </div>

  {{-- Encabezado --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
      <div>
        <div class="text-gray-500">Tipo</div>
        <div class="font-semibold">{{ $comprobante['tipo_comprobante']=='I' ? 'Ingreso' : 'Egreso' }}</div>
      </div>
      <div>
        <div class="text-gray-500">Serie/Folio</div>
        <div class="font-semibold">{{ $comprobante['serie'] }}-{{ $comprobante['folio'] }}</div>
      </div>
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
      <div>
        <div class="text-gray-500">Cliente</div>
        <div class="font-semibold">{{ $cliente->razon_social }} — {{ $cliente->rfc }}</div>
      </div>
    </div>
  </div>

  {{-- Conceptos --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6 overflow-x-auto">
    <table class="table-auto w-full text-sm">
      <thead class="text-xs uppercase text-gray-500 border-b">
        <tr>
          <th class="px-2 py-2 text-left w-28">Clave</th>
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
            $imp = 0.0;
            foreach (($c['impuestos'] ?? []) as $i) {
              if (($i['factor'] ?? '') === 'Exento') continue;
              $tasa = (float)($i['tasa'] ?? 0)/100;
              $m = $base * $tasa;
              $imp += (($i['tipo'] ?? 'T')==='R') ? -$m : $m;
            }
            $importe = $base + $imp;
          @endphp
          <tr class="border-b border-gray-100">
            <td class="px-2 py-2 align-top">{{ $c['clave_prod_serv'] }}/{{ $c['clave_unidad'] }}</td>
            <td class="px-2 py-2 align-top"><div class="font-medium">{{ $c['descripcion'] }}</div><div class="text-xs text-gray-500">Unidad: {{ $c['unidad'] }}</div></td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($c['cantidad'],3) }}</td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($c['precio'],2) }}</td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($des,2) }}</td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($importe,2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Totales --}}
  <div class="flex justify-end">
    <div class="w-full max-w-sm space-y-1 text-sm">
      <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ number_format($totales['subtotal'],2) }}</span></div>
      <div class="flex justify-between"><span class="text-gray-500">Descuento</span><span>{{ number_format($totales['descuento'],2) }}</span></div>
      <div class="flex justify-between"><span class="text-gray-500">Impuestos</span><span>{{ number_format($totales['impuestos'],2) }}</span></div>
      <div class="flex justify-between font-semibold text-gray-700"><span>Total</span><span>{{ number_format($totales['total'],2) }}</span></div>
    </div>
  </div>

  {{-- Acciones obligatorias desde preview --}}
  <form method="POST" action="{{ route('facturas.guardar') }}" class="mt-6 inline-block">
    @csrf
    <input type="hidden" name="payload" value="{{ e(json_encode($comprobante)) }}">
    <button class="btn bg-gray-100 hover:opacity-90">Guardar borrador</button>
  </form>
  <form method="POST" action="{{ route('facturas.timbrar') }}" class="mt-6 inline-block ml-2">
    @csrf
    <input type="hidden" name="payload" value="{{ e(json_encode($comprobante)) }}">
    <button class="btn bg-violet-600 hover:bg-violet-700 text-white">Timbrar</button>
  </form>
</div>
@endsection
