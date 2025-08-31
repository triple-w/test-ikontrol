@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Empleados (RFC: {{ session('rfc_seleccionado') ?? '—' }})</h1>
    <div class="flex gap-2">
      <form method="GET" class="flex gap-2">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar nombre / RFC / No. empleado / Puesto" class="rounded-md border p-2 text-sm">
      </form>
      <a href="{{ route('empleados.create') }}" class="px-3 py-2 rounded-md bg-violet-600 text-white text-sm">Nuevo empleado</a>
    </div>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4">Nombre</th>
            <th class="py-2 pr-4">RFC</th>
            <th class="py-2 pr-4">No. empleado</th>
            <th class="py-2 pr-4">Puesto</th>
            <th class="py-2 pr-4">Teléfono</th>
            <th class="py-2 pr-4">Email</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($items as $e)
            <tr class="hover:bg-gray-50">
              <td class="py-2 pr-4">{{ $e->nombre }}</td>
              <td class="py-2 pr-4 font-mono">{{ $e->rfc ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $e->numero_empleado ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $e->puesto ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $e->telefono ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $e->email ?? '—' }}</td>
              <td class="py-2 pr-4">
                <div class="flex gap-3">
                  <a href="{{ route('empleados.edit', $e) }}" class="text-violet-600 hover:underline text-sm">Editar</a>
                  <form method="POST" action="{{ route('empleados.destroy', $e) }}" onsubmit="return confirm('¿Eliminar empleado?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-rose-600 hover:underline text-sm">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="py-6 text-center text-gray-500">Sin empleados.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
  </div>
</div>
@endsection
