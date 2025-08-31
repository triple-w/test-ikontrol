@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Asignación de timbres (Admin)</h1>
    <a href="{{ route('admin.timbres.history') }}" class="px-3 py-2 rounded-md border text-sm">Historial</a>
  </div>

  @if(session('ok'))
    <div class="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ session('error') }}</div>
  @endif

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:col-span-1">
      <h2 class="font-semibold mb-3">Asignar a RFC</h2>
      <form method="POST" action="{{ route('admin.timbres.store') }}" class="space-y-4">
        @csrf
        <div>
          <label class="block text-sm mb-1">RFC</label>
          <select name="rfc_id" class="w-full rounded-md border p-2" required>
            <option value="">— Selecciona —</option>
            @foreach($rfcs as $r)
              <option value="{{ $r->id }}">{{ $r->rfc }} — {{ \Illuminate\Support\Str::limit($r->razon_social,38) }}</option>
            @endforeach
          </select>
          @error('rfc_id')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm mb-1">Cantidad</label>
          <input type="number" name="cantidad" min="1" class="w-full rounded-md border p-2" required>
          @error('cantidad')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm mb-1">Referencia (opcional)</label>
          <input type="text" name="referencia" class="w-full rounded-md border p-2" placeholder="Orden de compra, folio, etc.">
        </div>
        <button class="px-3 py-2 rounded-md bg-violet-600 text-white text-sm">Asignar</button>
      </form>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:col-span-2">
      <h2 class="font-semibold mb-3">Cuentas por RFC</h2>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="py-2 pr-3">RFC</th>
              <th class="py-2 pr-3">Razón social</th>
              <th class="py-2 pr-3">Asignados</th>
              <th class="py-2 pr-3">Consumidos</th>
              <th class="py-2 pr-3">Disponibles</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @foreach($rfcs as $r)
              @php
                $c = $cuentas->get($r->id);
                $asig = $c->asignados_total ?? 0;
                $cons = $c->consumidos_total ?? 0;
                $disp = max(0, $asig - $cons);
              @endphp
              <tr>
                <td class="py-2 pr-3 font-mono">{{ $r->rfc }}</td>
                <td class="py-2 pr-3">{{ $r->razon_social }}</td>
                <td class="py-2 pr-3">{{ $asig }}</td>
                <td class="py-2 pr-3">{{ $cons }}</td>
                <td class="py-2 pr-3 {{ $disp <= 10 ? 'text-amber-600 font-semibold' : '' }}">{{ $disp }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
