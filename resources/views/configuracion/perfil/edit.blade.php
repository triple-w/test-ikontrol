@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6 space-y-6">
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-xl font-semibold">Perfil del RFC</h1>
      <p class="text-sm text-gray-500">
        Edita los datos fiscales y de contacto para: <strong>{{ $rfc->rfc }}</strong>
      </p>
    </div>
    <a href="{{ route('sellos.index') }}" class="px-3 py-2 rounded-md border text-sm">Ir a Sellos</a>
  </div>

  @if(session('ok'))
    <div class="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif

  <form method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 sm:p-6 space-y-6">
    @csrf
    @method('PUT')

    <section>
      <h2 class="text-base font-semibold mb-3">Datos fiscales</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-sm mb-1">Razón Social</label>
          <input type="text" name="razon_social" value="{{ old('razon_social', $rfc->razon_social) }}" class="w-full rounded-md border p-2">
          @error('razon_social')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm mb-1">Régimen Fiscal</label>
          <input type="text" name="regimen_fiscal" value="{{ old('regimen_fiscal', $rfc->regimen_fiscal) }}" class="w-full rounded-md border p-2" placeholder="Ej. 601">
          @error('regimen_fiscal')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm mb-1">C.P. (lugar de expedición)</label>
          <input type="text" name="cp_expedicion" value="{{ old('cp_expedicion', $rfc->cp_expedicion) }}" class="w-full rounded-md border p-2" maxlength="5">
          @error('cp_expedicion')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </section>

    <section>
      <h2 class="text-base font-semibold mb-3">Domicilio fiscal</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div><label class="block text-sm mb-1">Calle</label><input type="text" name="calle" value="{{ old('calle', $rfc->calle) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">No. exterior</label><input type="text" name="no_ext" value="{{ old('no_ext', $rfc->no_ext) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">No. interior</label><input type="text" name="no_int" value="{{ old('no_int', $rfc->no_int) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">Colonia</label><input type="text" name="colonia" value="{{ old('colonia', $rfc->colonia) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">Municipio/Alcaldía</label><input type="text" name="municipio" value="{{ old('municipio', $rfc->municipio) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">Localidad</label><input type="text" name="localidad" value="{{ old('localidad', $rfc->localidad) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">Estado</label><input type="text" name="estado" value="{{ old('estado', $rfc->estado) }}" class="w-full rounded-md border p-2"></div>
        <div><label class="block text-sm mb-1">Código Postal</label><input type="text" name="codigo_postal" value="{{ old('codigo_postal', $rfc->codigo_postal) }}" class="w-full rounded-md border p-2" maxlength="5"></div>
      </div>
    </section>

    <section>
      <h2 class="text-base font-semibold mb-3">Contacto</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2">
          <label class="block text-sm mb-1">Correo CFDI / avisos</label>
          <input type="email" name="email" value="{{ old('email', $rfc->email) }}" class="w-full rounded-md border p-2">
          @error('email')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="block text-sm mb-1">Teléfono</label>
          <input type="text" name="telefono" value="{{ old('telefono', $rfc->telefono) }}" class="w-full rounded-md border p-2">
          @error('telefono')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </section>

    <section>
      <h2 class="text-base font-semibold mb-3">Branding</h2>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm mb-1">Logo (PNG/JPG)</label>
          <input type="file" name="logo" accept="image/*" class="block">
          @if($rfc->logo_path)
            <p class="text-xs text-gray-500 mt-1">Logo actual: {{ basename($rfc->logo_path) }}</p>
          @endif
          @error('logo')<p class="text-rose-600 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
    </section>

    <div class="flex justify-end">
      <button class="px-4 py-2 rounded-md bg-violet-600 text-white text-sm">Guardar</button>
    </div>
  </form>
</div>
@endsection
