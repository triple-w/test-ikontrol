@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <h1 class="text-xl font-semibold mb-4">Nuevo cliente</h1>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    @if($errors->any())
    <div class="mb-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
        Corrige los campos marcados y vuelve a intentar.
    </div>
    @endif
    @include('errors')
    <form method="POST" action="{{ route('clientes.store') }}" class="space-y-4">
      @include('catalogos.clientes._form', ['cliente' => null])
      <div class="flex gap-2">
        <a href="{{ route('clientes.index') }}" class="px-3 py-2 rounded-md border text-sm">Cancelar</a>
        <button class="px-3 py-2 rounded-md bg-violet-600 text-white text-sm">Guardar</button>
      </div>
    </form>
  </div>
</div>
@endsection
