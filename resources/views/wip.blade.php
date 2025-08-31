@extends('layouts.app')

@section('content')
  <div class="p-6">
    <h1 class="text-xl font-semibold mb-2">{{ $titulo ?? 'En construcción' }}</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">Esta sección estará disponible en breve.</p>
  </div>
@endsection
