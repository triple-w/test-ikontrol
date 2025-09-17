@extends('layouts.app')

@section('title','Borradores')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Borradores</h1>
    <a href="{{ route('facturas.create') }}" class="btn bg-violet-600 hover:bg-violet-700 text-white">Nuevo</a>
  </div>

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
    <table class="table-auto w-full text-sm">
      <thead class="text-xs uppercase text-gray-500 border-b">
        <tr>
          <th class="px-3 py-2 text-left">ID</th>
          <th class="px-3 py-2 text-left">Fecha</th>
          <th class="px-3 py-2 text-left">Cliente</th>
          <th class="px-3 py-2 text-left">Tipo</th>
          <th class="px-3 py-2 text-left">Serie/Folio</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-right">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($borradores as $b)
        <tr class="border-b border-gray-100">
          <td class="px-3 py-2 align-top">#{{ $b->id }}</td>
          <td class="px-3 py-2 align-top">{{ optional($b->fecha)->format('Y-m-d H:i') }}</td>
          <td class="px-3 py-2 align-top">{{ $clientes[$b->cliente_id] ?? '—' }}</td>
          <td class="px-3 py-2 align-top">{{ $b->tipo === 'I' ? 'Ingreso' : 'Egreso' }}</td>
          <td class="px-3 py-2 align-top">{{ $b->serie }}-{{ $b->folio }}</td>
          <td class="px-3 py-2 align-top text-right">{{ number_format($b->total,2) }}</td>
          <td class="px-3 py-2 align-top">
            <div class="flex justify-end gap-2">
              <a href="{{ route('borradores.edit',$b) }}" class="btn-xs bg-gray-100 hover:opacity-90">Editar</a>

              <form method="POST" action="{{ route('facturas.preview') }}">
                @csrf
                <input type="hidden" name="payload" value='@json($b->payload)'>
                <button class="btn-xs bg-violet-600 hover:bg-violet-700 text-white">Previsualizar</button>
              </form>

              <form method="POST" action="{{ route('borradores.destroy',$b) }}" onsubmit="return confirm('¿Eliminar borrador #{{ $b->id }}?')">
                @csrf @method('DELETE')
                <button class="btn-xs text-red-500 hover:text-red-600">Eliminar</button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" class="px-3 py-6 text-center text-gray-500">Sin borradores.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
