@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Complemento de pago — {{ $complemento->uuid ?? 'Sin UUID' }}</h1>
    <div class="flex gap-2">
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('complementos.pdf', $complemento) }}">Descargar PDF</a>
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('complementos.xml', $complemento) }}">Descargar XML</a>
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('complementos.index') }}">Regresar</a>
    </div>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
      <div class="p-4 rounded-md border">
        <div class="font-semibold mb-1">Receptor</div>
        <div>{{ $complemento->razon_social }}</div>
        <div class="text-gray-500">{{ $complemento->rfc }}</div>
        <div class="mt-2"><strong>Fecha:</strong> {{ optional($complemento->fecha)->format('Y-m-d H:i') ?? '—' }}</div>
        <div><strong>Estatus:</strong> {{ ucfirst($complemento->estatus) }}</div>
      </div>
      <div class="p-4 rounded-md border md:col-span-2">
        <div class="font-semibold mb-3">Pagos</div>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-gray-500 border-b">
                <th class="py-2 pr-3">Fecha pago</th>
                <th class="py-2 pr-3">Parcialidad</th>
                <th class="py-2 pr-3">Saldo anterior</th>
                <th class="py-2 pr-3">Monto</th>
                <th class="py-2 pr-3">Saldo insoluto</th>
                <th class="py-2 pr-3">Documento</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              @forelse($parsed['pagos'] as $p)
                <tr>
                  <td class="py-2 pr-3">{{ $p['fecha_pago'] ?? '—' }}</td>
                  <td class="py-2 pr-3">{{ $p['parcialidad'] ?? '—' }}</td>
                  <td class="py-2 pr-3">{{ $p['saldo_anterior'] !== null ? number_format($p['saldo_anterior'],2) : '—' }}</td>
                  <td class="py-2 pr-3">{{ $p['monto_pago'] !== null ? number_format($p['monto_pago'],2) : '—' }}</td>
                  <td class="py-2 pr-3">{{ $p['saldo_insoluto'] !== null ? number_format($p['saldo_insoluto'],2) : '—' }}</td>
                  <td class="py-2 pr-3 font-mono">{{ $p['documento'] ?? '—' }}</td>
                </tr>
              @empty
                <tr><td colspan="6" class="py-4 text-center text-gray-500">Sin pagos en la tabla ni en el XML.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-4 bg-gray-50 dark:bg-gray-900/20 rounded-md p-3 text-sm">
          <div class="flex justify-between font-semibold">
            <span>Total pagado</span>
            <span>{{ number_format($parsed['totales']['monto'] ?? 0, 2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="text-xs text-gray-500">
      * Esta es una representación visual basada en los datos disponibles. Los documentos oficiales están en “Descargar PDF/XML”.
    </div>
  </div>
</div>
@endsection
