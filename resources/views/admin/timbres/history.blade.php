@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Historial de movimientos (Admin)</h1>
    <a href="{{ route('admin.timbres.index') }}" class="px-3 py-2 rounded-md border text-sm">Asignaciones</a>
  </div>

  <form method="GET" action="{{ route('admin.timbres.history') }}" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 mb-4">
    <div class="grid sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-sm mb-1">RFC</label>
        <select name="rfc_id" class="w-full rounded-md border p-2">
          <option value="">— Todos —</option>
          @foreach($rfcs as $r)
            <option value="{{ $r->id }}" {{ (string)$r->id === (string)$rfcId ? 'selected' : '' }}>{{ $r->rfc }} — {{ \Illuminate\Support\Str::limit($r->razon_social,38) }}</option>
          @endforeach
        </select>
      </div>
      <div class="self-end">
        <button class="px-3 py-2 rounded-md border text-sm">Filtrar</button>
      </div>
    </div>
  </form>

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-3">Fecha</th>
            <th class="py-2 pr-3">RFC</th>
            <th class="py-2 pr-3">Tipo</th>
            <th class="py-2 pr-3">Cantidad</th>
            <th class="py-2 pr-3">Referencia</th>
            <th class="py-2 pr-3">Usuario</th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @foreach($movs as $m)
            <tr>
              <td class="py-2 pr-3">{{ $m->created_at->format('Y-m-d H:i') }}</td>
              <td class="py-2 pr-3 font-mono">{{ $m->rfc->rfc ?? '—' }}</td>
              <td class="py-2 pr-3">{{ $m->tipo }}</td>
              <td class="py-2 pr-3">{{ $m->cantidad }}</td>
              <td class="py-2 pr-3">{{ $m->referencia }}</td>
              <td class="py-2 pr-3">{{ $m->usuario->name ?? $m->user_id }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-3">
      {{ $movs->withQueryString()->links() }}
    </div>
  </div>
</div>
@endsection
