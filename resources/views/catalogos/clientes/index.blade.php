@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Clientes (RFC activo: {{ session('rfc_seleccionado') ?? '—' }})</h1>

    <form method="GET" class="flex gap-2">
      <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar..."
             class="rounded-md border p-2 text-sm">
      <a href="{{ route('clientes.create') }}"
         class="inline-flex items-center rounded-md bg-violet-600 px-3 py-2 text-white text-sm hover:bg-violet-700">
        Nuevo
      </a>
    </form>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="mb-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ $errors->first() }}
    </div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 dark:text-gray-300 border-b">
            <th class="py-2 pr-4">RFC</th>
            <th class="py-2 pr-4">Razón social</th>
            <th class="py-2 pr-4">Email</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($clientes as $c)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
              <td class="py-2 pr-4">{{ $c->rfc }}</td>
              <td class="py-2 pr-4">{{ $c->razon_social }}</td>
              <td class="py-2 pr-4">{{ $c->email ?? '—' }}</td>
              <td class="py-2 pr-4">
                <div class="flex gap-2">
                  <a href="{{ route('clientes.edit', $c) }}" class="text-violet-600 hover:underline text-sm">Editar</a>
                  <form method="POST" action="{{ route('clientes.destroy', $c) }}"
                        onsubmit="return confirm('¿Eliminar cliente?');">
                    @csrf @method('DELETE')
                    <button class="text-red-600 hover:underline text-sm" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="py-6 text-center text-gray-500">Sin clientes.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">
      {{ $clientes->links() }}
    </div>
  </div>
</div>
@endsection
