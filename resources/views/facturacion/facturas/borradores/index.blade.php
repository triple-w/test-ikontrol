@extends('layouts.app')
@section('title','Borradores de factura')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Borradores</h1>
    <a href="{{ route('facturas.create') }}" class="btn bg-violet-600 hover:bg-violet-700 text-white">+ Nueva factura</a>
  </div>

  <form method="GET" class="mb-3">
    <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por serie, folio o comentario..."
      class="form-input w-full sm:w-80" />
  </form>

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead>
        <tr class="text-left text-gray-500 border-b">
          <th class="px-3 py-2">ID</th>
          <th class="px-3 py-2">Serie-Folio</th>
          <th class="px-3 py-2">Cliente</th>
          <th class="px-3 py-2 text-right">Subtotal</th>
          <th class="px-3 py-2 text-right">Impuestos</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2">Estatus</th>
          <th class="px-3 py-2">Fecha</th>
          <th class="px-3 py-2">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr class="border-b border-gray-100">
            <td class="px-3 py-2">#{{ $r->id }}</td>
            <td class="px-3 py-2">{{ $r->serie }}-{{ $r->folio }}</td>
            <td class="px-3 py-2">
              {{-- Si quieres mostrar nombre, necesitaríamos join. De momento, muestra ID --}}
              ID: {{ $r->cliente_id }}
            </td>
            <td class="px-3 py-2 text-right">{{ number_format($r->subtotal,2) }}</td>
            <td class="px-3 py-2 text-right">{{ number_format($r->impuestos,2) }}</td>
            <td class="px-3 py-2 text-right font-semibold">{{ number_format($r->total,2) }}</td>
            <td class="px-3 py-2">
              <span class="px-2 py-1 rounded bg-amber-100 text-amber-800 text-xs">{{ strtoupper($r->estatus) }}</span>
            </td>
            <td class="px-3 py-2">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
            <td class="px-3 py-2">
              <a href="{{ route('facturas.borradores.open', $r) }}" class="text-violet-700 hover:underline">Editar</a>
              <form action="{{ route('facturas.borradores.destroy', $r) }}" method="POST" class="inline"
                    onsubmit="return confirm('¿Eliminar borrador #{{ $r->id }}?')">
                @csrf @method('DELETE')
                <button class="text-red-600 hover:underline ml-2" type="submit">Eliminar</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">No hay borradores.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $rows->withQueryString()->links() }}
  </div>
</div>
@endsection
