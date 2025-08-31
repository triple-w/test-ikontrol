@csrf
@php $p = $producto ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm mb-1">Clave interna</label>
    <input name="clave" value="{{ old('clave', $p->clave ?? '') }}" class="w-full rounded-md border p-2">
  </div>

  <div>
    <label class="block text-sm mb-1">Unidad (texto)</label>
    <input name="unidad" value="{{ old('unidad', $p->unidad ?? '') }}" class="w-full rounded-md border p-2">
  </div>

  <div>
    <label class="block text-sm mb-1">Precio</label>
    <input name="precio" type="number" step="0.01" min="0"
           value="{{ old('precio', $p->precio ?? 0) }}" class="w-full rounded-md border p-2">
    @error('precio')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm mb-1">Descripción</label>
    <input name="descripcion" value="{{ old('descripcion', $p->descripcion ?? '') }}" class="w-full rounded-md border p-2">
    @error('descripcion')<div class="text-xs text-red-500">{{ $message }}</div>@enderror
  </div>

  <div class="md:col-span-2">
    <label class="block text-sm mb-1">Observaciones</label>
    <textarea name="observaciones" rows="3" class="w-full rounded-md border p-2">{{ old('observaciones', $p->observaciones ?? '') }}</textarea>
  </div>

  {{-- Clave Producto/Servicio SAT --}}
  <div x-data="catalogSearch(
          '{{ route('catalogos.search.prodserv') }}',
          {{ json_encode(old('clave_prod_serv_id', $p->clave_prod_serv_id ?? null)) }}
        )">
    <label class="block text-sm mb-1">Clave Prod/Serv SAT</label>
    <input type="hidden" name="clave_prod_serv_id" x-model="selectedId">
    <input type="text" x-model="term" @input.debounce.300ms="search()" placeholder="Busca por clave o descripción"
           class="w-full rounded-md border p-2">
    <template x-if="selectedLabel">
      <div class="text-xs text-gray-500 mt-1">Seleccionado: <span x-text="selectedLabel"></span></div>
    </template>
    <ul class="mt-1 border rounded-md max-h-48 overflow-auto" x-show="results.length">
      <template x-for="r in results" :key="r.id">
        <li class="px-2 py-1 hover:bg-gray-100 cursor-pointer" @click="choose(r)">
          <span class="font-mono" x-text="r.clave"></span> — <span x-text="r.descripcion"></span>
        </li>
      </template>
    </ul>
  </div>

  {{-- Clave Unidad SAT --}}
  <div x-data="catalogSearch(
          '{{ route('catalogos.search.unidades') }}',
          {{ json_encode(old('clave_unidad_id', $p->clave_unidad_id ?? null)) }}
        )">
    <label class="block text-sm mb-1">Clave Unidad SAT</label>
    <input type="hidden" name="clave_unidad_id" x-model="selectedId">
    <input type="text" x-model="term" @input.debounce.300ms="search()" placeholder="Busca por clave o descripción"
           class="w-full rounded-md border p-2">
    <template x-if="selectedLabel">
      <div class="text-xs text-gray-500 mt-1">Seleccionado: <span x-text="selectedLabel"></span></div>
    </template>
    <ul class="mt-1 border rounded-md max-h-48 overflow-auto" x-show="results.length">
      <template x-for="r in results" :key="r.id">
        <li class="px-2 py-1 hover:bg-gray-100 cursor-pointer" @click="choose(r)">
          <span class="font-mono" x-text="r.clave"></span> — <span x-text="r.descripcion"></span>
        </li>
      </template>
    </ul>
  </div>
</div>

<script>
function catalogSearch(url, initialId) {
  return {
    term: '',
    results: [],
    selectedId: initialId || null,
    selectedLabel: '',
    async init() {
      // precargar etiqueta cuando editamos
      if (this.selectedId) {
        const res = await fetch(`${url}?id=${this.selectedId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        const data = await res.json();
        if (data.length) {
          this.selectedLabel = `${data[0].clave} — ${data[0].descripcion}`;
        }
      }
    },
    async search() {
      if (!this.term || this.term.length < 2) { this.results = []; return; }
      const res = await fetch(`${url}?term=${encodeURIComponent(this.term)}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
      this.results = await res.json();
    },
    choose(r) {
      this.selectedId = r.id;
      this.selectedLabel = `${r.clave} — ${r.descripcion}`;
      this.term = '';
      this.results = [];
    }
  }
}
</script>
