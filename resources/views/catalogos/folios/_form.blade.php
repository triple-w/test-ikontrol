@csrf
@php
  $f = $folio ?? null;
  $tipos = [
    'ingreso'  => 'Ingreso (Factura)',
    'egreso'   => 'Egreso (Nota de crédito / Nómina según tu uso)',
    'traslado' => 'Traslado',
    'pagos'    => 'Pagos (Complemento de pago)',
  ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
  <div>
    <label class="block text-sm mb-1">Tipo</label>
    <select name="tipo" class="w-full rounded-md border p-2" required>
      <option value="">Selecciona…</option>
      @foreach($tipos as $k => $txt)
        <option value="{{ $k }}" @selected(old('tipo', $f->tipo ?? '') === $k)>{{ $txt }}</option>
      @endforeach
    </select>
    @error('tipo')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  <div>
    <label class="block text-sm mb-1">Serie (prefijo)</label>
    <input name="serie" value="{{ old('serie', $f->serie ?? '') }}" class="w-full rounded-md border p-2"
           maxlength="10" oninput="this.value=this.value.toUpperCase();" placeholder="Ejm: A, F1, T-01">
    @error('serie')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  <div>
    <label class="block text-sm mb-1">Folio actual</label>
    <input name="folio" type="number" min="0" value="{{ old('folio', $f->folio ?? 0) }}"
           class="w-full rounded-md border p-2">
    <div class="text-xs text-gray-500 mt-1">Este es el número actual. El siguiente documento usará <strong>folio + 1</strong>.</div>
    @error('folio')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>
</div>
