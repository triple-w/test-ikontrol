@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Productos (RFC: {{ session('rfc_seleccionado') ?? '—' }})</h1>

    <form method="GET" class="flex gap-2">
      <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar..."
             class="rounded-md border p-2 text-sm">
      <a href="{{ route('productos.create') }}"
         class="inline-flex items-center rounded-md bg-violet-600 px-3 py-2 text-white text-sm hover:bg-violet-700">
        Nuevo
      </a>
    </form>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 dark:text-gray-300 border-b">
            <th class="py-2 pr-4">Clave</th>
            <th class="py-2 pr-4">Descripción</th>
            <th class="py-2 pr-4">Precio</th>
            <th class="py-2 pr-4">Prod/Serv</th>
            <th class="py-2 pr-4">Unidad</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($items as $it)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="py-2 pr-4">{{ $it->clave ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $it->descripcion }}</td>
              <td class="py-2 pr-4">{{ number_format($it->precio, 2) }}</td>
              <td class="py-2 pr-4">
                @if($it->prodServ)
                  <span class="font-mono">{{ $it->prodServ->clave }}</span> — {{ $it->prodServ->descripcion }}
                @else
                  —
                @endif
              </td>
              <td class="py-2 pr-4">
                @if($it->unidadSat)
                  <span class="font-mono">{{ $it->unidadSat->clave }}</span> — {{ $it->unidadSat->descripcion }}
                @else
                  —
                @endif
              </td>
              <td class="py-2 pr-4">
                <div class="flex gap-2">
                  <a href="{{ route('productos.edit', $it) }}" class="text-violet-600 hover:underline text-sm">Editar</a>
                  <form method="POST" action="{{ route('productos.destroy', $it) }}" onsubmit="return confirm('¿Eliminar producto?');">
                    @csrf @method('DELETE')
                    <button class="text-red-600 hover:underline text-sm" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="py-6 text-center text-gray-500">Sin productos.</td></tr>
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
