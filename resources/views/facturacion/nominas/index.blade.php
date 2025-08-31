@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Nóminas (RFC: {{ session('rfc_seleccionado') ?? '—' }})</h1>
    <form method="GET" class="flex gap-2">
      <select name="estatus" class="rounded-md border p-2 text-sm">
        <option value="">Estatus</option>
        <option value="borrador"  @selected(request('estatus')==='borrador')>Borrador</option>
        <option value="timbrado"  @selected(request('estatus')==='timbrado')>Timbrado</option>
        <option value="cancelado" @selected(request('estatus')==='cancelado')>Cancelado</option>
      </select>
      <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="UUID / Empleado / RFC" class="rounded-md border p-2 text-sm">
    </form>
  </div>

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4">Empleado</th>
            <th class="py-2 pr-4">RFC</th>
            <th class="py-2 pr-4">Fecha</th>
            <th class="py-2 pr-4">Estatus</th>
            <th class="py-2 pr-4">UUID</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($items as $n)
            <tr class="hover:bg-gray-50">
              <td class="py-2 pr-4">{{ $n->empleado->nombre ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $n->empleado->rfc ?? '—' }}</td>
              <td class="py-2 pr-4">{{ optional($n->fecha)->format('Y-m-d H:i') ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $n->estatus }}</td>
              <td class="py-2 pr-4 font-mono">{{ $n->uuid ?? '—' }}</td>
              <td class="py-2 pr-4">
                <div class="flex gap-3">
                  <a href="{{ route('nominas.show', $n) }}" class="text-violet-600 hover:underline text-sm">Ver</a>
                  <a href="{{ route('nominas.pdf',  $n) }}" class="text-violet-600 hover:underline text-sm">PDF</a>
                  <a href="{{ route('nominas.xml',  $n) }}" class="text-violet-600 hover:underline text-sm">XML</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="py-6 text-center text-gray-500">Sin nóminas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
  </div>
</div>
@endsection
