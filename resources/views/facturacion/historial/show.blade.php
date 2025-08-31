@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Factura — {{ $factura->uuid ?? 'Sin UUID' }}</h1>
    <div class="flex gap-2">
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('facturas.pdf', $factura) }}">Descargar PDF</a>
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('facturas.xml', $factura) }}">Descargar XML</a>
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('facturas.index') }}">Regresar</a>
    </div>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif

  {{-- Invoice básico con los datos disponibles en la tabla facturas --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-6">
  {{-- Encabezado --}}
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <div>
      <div class="text-lg font-semibold">CFDI {{ strtoupper($factura->tipo_comprobante ?? '—') }}</div>
      <div class="text-sm text-gray-500">UUID: {{ $factura->uuid ?? '—' }}</div>
      <div class="text-sm text-gray-500">Estatus: {{ ucfirst($factura->estatus) }}</div>
    </div>
    <div class="text-sm">
      <div><strong>Fecha CFDI:</strong> {{ optional($factura->fecha_factura)->format('Y-m-d H:i') ?? '—' }}</div>
      <div><strong>Fecha Registro:</strong> {{ optional($factura->fecha)->format('Y-m-d H:i') ?? '—' }}</div>
    </div>
  </div>

  {{-- Receptor (snapshot) --}}
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
    <div class="p-4 rounded-md border">
      <div class="font-semibold mb-1">Receptor</div>
      <div>{{ $factura->razon_social }}</div>
      <div class="text-gray-500">{{ $factura->rfc }}</div>
      <div class="mt-2">
        <div>{{ $factura->calle }} {{ $factura->no_ext }} {{ $factura->no_int ? '#'.$factura->no_int : '' }}</div>
        <div>{{ $factura->colonia }}, {{ $factura->municipio }}, {{ $factura->estado }}</div>
        <div>{{ $factura->codigo_postal }} {{ $factura->pais }}</div>
      </div>
      @if($factura->telefono || $factura->nombre_contacto)
        <div class="mt-2 text-gray-500">
          {{ $factura->nombre_contacto ?? '' }} {{ $factura->telefono ? '· '.$factura->telefono : '' }}
        </div>
      @endif
    </div>

    <div class="p-4 rounded-md border md:col-span-2">
      <div class="font-semibold mb-3">Conceptos</div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="py-2 pr-3">Clave</th>
              <th class="py-2 pr-3">Descripción</th>
              <th class="py-2 pr-3">Cant.</th>
              <th class="py-2 pr-3">Unidad</th>
              <th class="py-2 pr-3">V. Unit.</th>
              <th class="py-2 pr-3">Desc.</th>
              <th class="py-2 pr-3">Importe</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @forelse($cfdi['conceptos'] as $c)
              <tr>
                <td class="py-2 pr-3 font-mono">{{ $c['clave_prod_serv'] }}</td>
                <td class="py-2 pr-3">{{ $c['descripcion'] }}</td>
                <td class="py-2 pr-3">{{ rtrim(rtrim(number_format($c['cantidad'], 3), '0'), '.') }}</td>
                <td class="py-2 pr-3">{{ $c['unidad'] }}</td>
                <td class="py-2 pr-3">{{ number_format($c['valor_unitario'], 2) }}</td>
                <td class="py-2 pr-3">{{ number_format($c['descuento'] ?? 0, 2) }}</td>
                <td class="py-2 pr-3">{{ number_format($c['importe'], 2) }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="py-4 text-center text-gray-500">Sin conceptos en el XML.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Totales --}}
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div></div>
    <div class="bg-gray-50 dark:bg-gray-900/20 rounded-md p-4 text-sm space-y-1">
      <div class="flex justify-between"><span>Subtotal</span><span>{{ number_format($cfdi['totales']['subtotal'] ?? 0, 2) }}</span></div>
      <div class="flex justify-between"><span>Descuento</span><span>{{ number_format($cfdi['totales']['descuento'] ?? 0, 2) }}</span></div>
      <div class="flex justify-between"><span>Impuestos trasladados</span><span>{{ number_format($cfdi['totales']['trasladados'] ?? 0, 2) }}</span></div>
      <div class="flex justify-between"><span>Impuestos retenidos</span><span>{{ number_format($cfdi['totales']['retenidos'] ?? 0, 2) }}</span></div>
      <div class="flex justify-between font-semibold text-base"><span>Total</span><span>{{ number_format($cfdi['totales']['total'] ?? 0, 2) }}</span></div>
    </div>
  </div>

  <div class="text-xs text-gray-500">
    * Esta es una representación visual basada en el XML y en los campos almacenados. Los documentos oficiales están en “Descargar PDF/XML”.
  </div>
</div>

</div>
@endsection
