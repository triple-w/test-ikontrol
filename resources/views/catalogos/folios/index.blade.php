@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Folios (RFC: {{ session('rfc_seleccionado') ?? '—' }})</h1>

    <form method="GET" class="flex gap-2">
      <select name="tipo" class="rounded-md border p-2 text-sm">
        <option value="">Todos</option>
        <option value="ingreso"  @selected(request('tipo')==='ingreso')>Ingreso</option>
        <option value="egreso"   @selected(request('tipo')==='egreso')>Egreso</option>
        <option value="traslado" @selected(request('tipo')==='traslado')>Traslado</option>
        <option value="pagos"    @selected(request('tipo')==='pagos')>Pagos</option>
      </select>
      <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar serie..."
             class="rounded-md border p-2 text-sm" />
      <a href="{{ route('folios.create') }}"
         class="inline-flex items-center rounded-md bg-violet-600 px-3 py-2 text-white text-sm hover:bg-violet-700">
        Nueva serie
      </a>
    </form>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">Corrige los campos marcados.</div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 dark:text-gray-300 border-b">
            <th class="py-2 pr-4">Tipo</th>
            <th class="py-2 pr-4">Serie</th>
            <th class="py-2 pr-4">Folio actual</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($items as $it)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="py-2 pr-4 capitalize">{{ $it->tipo }}</td>
              <td class="py-2 pr-4 font-mono">{{ $it->serie }}</td>
              <td class="py-2 pr-4">{{ $it->folio }}</td>
              <td class="py-2 pr-4">
                <div class="flex gap-2">
                  <a href="{{ route('folios.edit', $it) }}" class="text-violet-600 hover:underline text-sm">Editar</a>
                  <form method="POST" action="{{ route('folios.destroy', $it) }}" onsubmit="return confirm('¿Eliminar serie?');">
                    @csrf @method('DELETE')
                    <button class="text-red-600 hover:underline text-sm" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="py-6 text-center text-gray-500">Sin series registradas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">
      {{ $items->links() }}
    </div>
  </div>
</div>
@endsection
