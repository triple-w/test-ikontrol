@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <h1 class="text-xl font-semibold">Laboratorio de Timbrado (REST)</h1>
    @if($isPruebas)
      <span class="px-2 py-1 rounded bg-amber-100 text-amber-800 text-xs font-medium">PRUEBAS</span>
    @endif
  </div>

  @if (session('error'))
    <div class="p-3 rounded bg-rose-50 text-rose-700 text-sm">{{ session('error') }}</div>
  @endif
  @if (session('success'))
    <div class="p-3 rounded bg-emerald-50 text-emerald-700 text-sm">{{ session('success') }}</div>
  @endif

  @if (session('xmlPath') || session('pdfPath'))
    <div class="p-3 rounded border text-sm">
      <div>XML: @if(session('xmlPath')) <a class="text-violet-700 underline" href="{{ route('download.storage', ['path' => session('xmlPath')]) }}">descargar</a> @endif</div>
      <div>PDF: @if(session('pdfPath')) <a class="text-violet-700 underline" href="{{ route('download.storage', ['path' => session('pdfPath')]) }}">descargar</a> @else <span class="text-gray-500">no generado</span> @endif</div>
    </div>
  @endif

  <form method="POST" action="{{ route('admin.pac.timbrar') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-sm font-medium mb-1">Plantilla PDF (JSON2)</label>
      <input type="text" name="plantilla" class="w-full border rounded p-2 text-sm" value="{{ old('plantilla', $plantilla) }}" placeholder="1, 2, 3, pagos, nomina, cartaporte, etc.">
    </div>

    <div>
      <label class="block text-sm font-medium mb-1">JSON CFDI (opcional)</label>
      <textarea name="raw_json" rows="12" class="w-full border rounded p-2 text-xs" placeholder="Pega aquí el JSON del CFDI (si lo dejas vacío se usará un ejemplo mínimo)">{{ old('raw_json', json_encode($sampleJson, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) }}</textarea>
      <p class="text-xs text-gray-500 mt-1">El servicio JSON2 requiere keyPEM y cerPEM; en PRUEBAS tomamos los PEM desde <code>.env</code>.</p>
    </div>

    <button class="px-4 py-2 rounded bg-violet-600 text-white text-sm">Timbrar (JSON2)</button>
  </form>
</div>
@endsection
