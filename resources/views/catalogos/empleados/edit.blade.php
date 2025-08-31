@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Editar empleado</h1>
    <a href="{{ route('empleados.index') }}" class="px-3 py-2 rounded-md border text-sm">Regresar</a>
  </div>

  <form method="POST" action="{{ route('empleados.update', $empleado) }}" class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    @csrf @method('PUT')
    @include('catalogos.empleados._form', ['empleado' => $empleado])
    <div class="mt-6 flex justify-end">
      <button type="submit" class="px-3 py-2 rounded-md bg-violet-600 text-white text-sm">Actualizar</button>
    </div>
  </form>
</div>
@endsection
