@extends('layouts.app')

@section('title','Previsualización de factura')

@section('content')
@if(session('ok'))
  <div class="mb-4 p-3 rounded bg-green-50 text-green-700 text-sm">
    {{ session('ok') }}
  </div>
@endif

@if(session('error'))
  <div class="mb-4 p-3 rounded bg-red-50 text-red-700 text-sm">
    {{ session('error') }}
  </div>
@endif


<div class="max-w-5xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Previsualización</h1>
    <div class="flex items-center gap-2">
      <div class="text-sm text-gray-500">RFC emisor: <span class="font-medium">{{ $emisor_rfc }}</span></div>
      <button type="button" class="px-3 py-2 rounded-md border text-sm"
        onclick="if (window.history.length > 1) { history.back(); } else { window.location='{{ route('facturas.create') }}'; }">
        ← Regresar
      </button>

    </div>
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
      <thead>
        <tr class="text-left text-gray-500 border-b">
          <th class="px-2 py-2">Clave</th>
          <th class="px-2 py-2">Descripción</th>
          <th class="px-2 py-2 text-right">Cant.</th>
          <th class="px-2 py-2 text-right">V. Unit.</th>
          <th class="px-2 py-2 text-right">Desc.</th>
          <th class="px-2 py-2 text-right">Importe</th>
        </tr>
      </thead>
      <tbody>
        @foreach($comprobante['conceptos'] as $c)
          @php
            $sub = (float)$c['cantidad'] * (float)$c['precio'];
            $des = (float)($c['descuento'] ?? 0);
            $importe = max($sub - $des, 0);
          @endphp
          <tr class="border-b border-gray-100">
            <td class="px-2 py-2 align-top">{{ $c['clave_prod_serv'] }}/{{ $c['clave_unidad'] }}</td>
            <td class="px-2 py-2 align-top">
              <div class="font-medium">{{ $c['descripcion'] }}</div>
              @if(!empty($c['unidad']))
                <div class="text-xs text-gray-500">Unidad: {{ $c['unidad'] }}</div>
              @endif
            </td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($c['cantidad'],3) }}</td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($c['precio'],2) }}</td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($des,2) }}</td>
            <td class="px-2 py-2 text-right align-top">{{ number_format($importe,2) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  {{-- Documentos relacionados --}}
  @php($rels = $comprobante['relacionados'] ?? [])
  @if(is_array($rels) && count($rels))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
      <h3 class="text-sm font-semibold mb-2">Documentos relacionados</h3>
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-3">Tipo relación</th>
            <th class="py-2 pr-3">UUID</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rels as $r)
            <tr class="border-b border-gray-100">
              <td class="py-2 pr-3">{{ $r['tipo_relacion'] ?? '' }}</td>
              <td class="py-2 pr-3 font-mono text-xs">{{ $r['uuid'] ?? '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  {{-- Comentarios para PDF --}}
  @if(!empty($comprobante['comentarios_pdf']))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-6">
      <h3 class="text-sm font-semibold mb-2">Comentarios (PDF)</h3>
      <div class="text-sm whitespace-pre-line">{{ $comprobante['comentarios_pdf'] }}</div>
    </div>
  @endif

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
  <div class="mt-6">
    {{-- Botón que NO envía ningún form padre, sólo dispara el form oculto --}}
    <button type="button"
            class="btn bg-gray-100 hover:opacity-90"
            onclick="document.getElementById('formGuardarBorrador').submit()">
      Guardar borrador
    </button>

    {{-- Botón Timbrar igual, con form independiente (oculto) --}}
    <button type="button"
            class="btn bg-violet-600 hover:bg-violet-700 text-white ml-2"
            onclick="document.getElementById('formTimbrar').submit()">
      Timbrar
    </button>
  </div>
  {{-- DEBUG: ver adónde va a postear --}}
  <div class="mb-2 text-xs text-gray-500">
    debug action guardar: <code>{{ route('facturas.guardar') }}</code>
  </div>

  {{-- FORMULARIOS OCULTOS, INDEPENDIENTES (NO anidados) --}}
  <form id="formGuardarBorrador" method="POST" action="{{ route('facturas.guardar') }}" style="display:none">
    @csrf
    <input type="hidden" name="payload" value='@json($comprobante, JSON_UNESCAPED_UNICODE)'>

  </form>

  <form id="formTimbrar" method="POST" action="{{ route('facturas.timbrar') }}" style="display:none">
    @csrf
    <input type="hidden" name="payload" value="{{ e(json_encode($comprobante, JSON_UNESCAPED_UNICODE)) }}">
  </form>



</div>
@endsection
