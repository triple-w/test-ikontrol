@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Sellos digitales — {{ $rfcUsuario->rfc }}</h1>
    <a href="{{ route('perfil.edit') }}" class="px-3 py-2 rounded-md border text-sm">Perfil del RFC</a>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif
  @if(session('error'))
    <div class="mb-3 rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">
      {{ session('error') }}
    </div>
  @endif
  @if(session('warn'))
    <div class="mb-3 rounded-md bg-yellow-50 px-3 py-2 text-sm text-yellow-800">
      {{ session('warn') }}
    </div>
  @endif

  @if(session('api_debug'))
  <div class="mb-4 rounded-md bg-gray-50 p-3 text-xs">
    <details open>
      <summary class="cursor-pointer font-semibold mb-2">Depuración de validador</summary>
      <pre class="whitespace-pre-wrap">{{ json_encode(session('api_debug'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
      <p class="mt-2 text-gray-500">*Este panel solo aparece cuando hay error o depuración.</p>
    </details>
  </div>
@endif


  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Formulario: Agregar/validar CSD --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:col-span-1">
      <h2 class="font-semibold mb-3">Agregar CSD</h2>

      <form method="POST" action="{{ route('sellos.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
          <label class="block text-sm mb-1">Archivo .cer</label>
          <div class="flex items-center gap-3">
            <label for="cerInput" class="px-3 py-2 rounded-md border text-sm cursor-pointer select-none">
              Seleccionar .cer
            </label>
            <span id="cerName" class="text-sm text-gray-500">Ningún archivo seleccionado</span>
          </div>
          <input id="cerInput" type="file" name="cer" accept=".cer" class="sr-only" required>
          @error('cer')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="block text-sm mb-1">Archivo .key</label>
          <div class="flex items-center gap-3">
            <label for="keyInput" class="px-3 py-2 rounded-md border text-sm cursor-pointer select-none">
              Seleccionar .key
            </label>
            <span id="keyName" class="text-sm text-gray-500">Ningún archivo seleccionado</span>
          </div>
          <input id="keyInput" type="file" name="key" accept=".key" class="sr-only" required>
          @error('key')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
          <label class="block text-sm mb-1">Contraseña de la llave (.key)</label>
          <input type="password" name="password" class="w-full rounded-md border p-2" placeholder="••••••••" required>
          @error('password')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>

        <button class="px-3 py-2 rounded-md bg-violet-600 text-white text-sm">
          Validar y guardar
        </button>
      </form>
    </div>

    {{-- Lista de CSDs --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 lg:col-span-2">
      <h2 class="font-semibold mb-3">Certificados registrados</h2>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="text-left text-gray-500 border-b">
              <th class="py-2 pr-3">Nombre</th>
              <th class="py-2 pr-3">No. cert</th>
              <th class="py-2 pr-3">Vigencia</th>
              <th class="py-2 pr-3">Activo</th>
              <th class="py-2 pr-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @forelse($csds as $c)
              <tr>
                <td class="py-2 pr-3">{{ $c->nombre ?? '—' }}</td>
                <td class="py-2 pr-3 font-mono">{{ $c->no_certificado ?? '—' }}</td>
                <td class="py-2 pr-3">
                  {{ $c->vigencia_desde ? \Carbon\Carbon::parse($c->vigencia_desde)->format('d/m/Y') : '—' }}
                  —
                  {{ $c->vigencia_hasta ? \Carbon\Carbon::parse($c->vigencia_hasta)->format('d/m/Y') : '—' }}
                </td>
                <td class="py-2 pr-3">
                  @if($c->activo)
                    <span class="text-green-600">Sí</span>
                  @else
                    No
                  @endif
                </td>
                <td class="py-2 pr-3">
                  <div class="flex gap-3">
                    @if(!$c->activo)
                      <form method="POST" action="{{ route('sellos.activar', $c) }}">
                        @csrf
                        <button class="text-violet-600 hover:underline">Activar</button>
                      </form>
                    @endif
                    <form method="POST" action="{{ route('sellos.destroy', $c) }}" onsubmit="return confirm('¿Eliminar CSD?');">
                      @csrf
                      @method('DELETE')
                      <button class="text-rose-600 hover:underline">Eliminar</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="py-6 text-center text-gray-500">Sin CSDs.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('cerInput')?.addEventListener('change', (e) => {
    const name = e.target.files?.[0]?.name || 'Ningún archivo seleccionado';
    document.getElementById('cerName').textContent = name;
  });
  document.getElementById('keyInput')?.addEventListener('change', (e) => {
    const name = e.target.files?.[0]?.name || 'Ningún archivo seleccionado';
    document.getElementById('keyName').textContent = name;
  });
</script>
@endsection
