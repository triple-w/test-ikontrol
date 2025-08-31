@csrf
@php
  $cliente   = $cliente ?? null;
  $regimenes = (array) config('catalogos.regimenes_fiscales', []);
  $estadosMx = (array) config('catalogos.estados_mx', []);
  $selReg    = (string) old('regimen_fiscal', (string) ($cliente->regimen_fiscal ?? ''));
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  {{-- RFC (cliente/receptor) --}}
  <div class="md:col-span-1">
    <label class="block text-sm mb-1">RFC (cliente)</label>
    <input name="rfc"
           value="{{ old('rfc', $cliente->rfc ?? '') }}"
           class="w-full rounded-md border p-2"
           maxlength="13"
           pattern="[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}"
           title="Formato RFC válido (12 ó 13 caracteres: AAAA + fecha (yymmdd) + homoclave)"
           oninput="this.value = this.value.toUpperCase();">
    @error('rfc')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  {{-- Razón social --}}
  <div class="md:col-span-1">
    <label class="block text-sm mb-1">Razón social</label>
    <input name="razon_social" value="{{ old('razon_social', $cliente->razon_social ?? '') }}" class="w-full rounded-md border p-2">
    @error('razon_social')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  {{-- Email --}}
  <div>
    <label class="block text-sm mb-1">Email</label>
    <input name="email" type="email" value="{{ old('email', $cliente->email ?? '') }}" class="w-full rounded-md border p-2">
    @error('email')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  {{-- Teléfono --}}
  <div>
    <label class="block text-sm mb-1">Teléfono</label>
    <input name="telefono" value="{{ old('telefono', $cliente->telefono ?? '') }}" class="w-full rounded-md border p-2">
  </div>

  {{-- Régimen fiscal (solo clave) --}}
  <div>
    <label class="block text-sm mb-1">Régimen fiscal (SAT)</label>
    <select name="regimen_fiscal" class="w-full rounded-md border p-2" required>
      <option value="">Selecciona…</option>
      @foreach($regimenes as $clave => $texto)
        <option value="{{ $clave }}" {{ (string)$selReg === (string)$clave ? 'selected' : '' }}>
          {{ $clave }} — {{ $texto }}
        </option>
      @endforeach
    </select>
    @error('regimen_fiscal')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  {{-- Código postal --}}
  <div>
    <label class="block text-sm mb-1">Código postal</label>
    <input name="codigo_postal" value="{{ old('codigo_postal', $cliente->codigo_postal ?? '') }}" class="w-full rounded-md border p-2" maxlength="10">
  </div>

  {{-- Calle / No ext / No int --}}
  <div>
    <label class="block text-sm mb-1">Calle</label>
    <input name="calle" value="{{ old('calle', $cliente->calle ?? '') }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">No. ext</label>
    <input name="no_ext" value="{{ old('no_ext', $cliente->no_ext ?? '') }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">No. int</label>
    <input name="no_int" value="{{ old('no_int', $cliente->no_int ?? '') }}" class="w-full rounded-md border p-2">
  </div>

  {{-- Colonia / Municipio --}}
  <div>
    <label class="block text-sm mb-1">Colonia</label>
    <input name="colonia" value="{{ old('colonia', $cliente->colonia ?? '') }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Municipio</label>
    <input name="municipio" value="{{ old('municipio', $cliente->municipio ?? '') }}" class="w-full rounded-md border p-2">
  </div>

  {{-- Localidad / Estado (select) / País --}}
  <div>
    <label class="block text-sm mb-1">Localidad</label>
    <input name="localidad" value="{{ old('localidad', $cliente->localidad ?? '') }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Estado (México)</label>
    <select name="estado" class="w-full rounded-md border p-2">
      <option value="">Selecciona…</option>
      @foreach($estadosMx as $estado)
        <option value="{{ $estado }}" @selected(old('estado', $cliente->estado ?? '') === $estado)>{{ $estado }}</option>
      @endforeach
    </select>
    @error('estado')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="block text-sm mb-1">País</label>
    <input name="pais" value="{{ old('pais', $cliente->pais ?? 'México') }}" class="w-full rounded-md border p-2">
  </div>

  {{-- Nombre contacto --}}
  <div class="md:col-span-2">
    <label class="block text-sm mb-1">Nombre de contacto</label>
    <input name="nombre_contacto" value="{{ old('nombre_contacto', $cliente->nombre_contacto ?? '') }}" class="w-full rounded-md border p-2">
  </div>
</div>
