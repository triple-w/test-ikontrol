@extends('layouts.app')

@section('title','Crear factura')

@section('content')
@php
  // Para pasar clientes al front sin problemas de escape
  $clientesJson = json_encode($clientes, JSON_UNESCAPED_UNICODE);
@endphp

<div
  x-data='facturaForm({
    rfcUsuarioId: {{ (int) $rfcUsuarioId }},
    clientes: {!! $clientesJson !!},
    minFecha: "{{ $minFecha }}",
    maxFecha: "{{ $maxFecha }}",
    apiSeriesNext: "{{ url('/api/series/next') }}",
    apiProductosBuscar: "{{ url('/api/productos/buscar') }}",
    apiSatProdServ: "{{ url('/api/sat/clave-prod-serv') }}",
    apiSatUnidad: "{{ url('/api/sat/clave-unidad') }}",
    routeClienteUpdateBase: "{{ url('/catalogos/clientes') }}",
    csrf: "{{ csrf_token() }}",
    // opcionales si vienes de editar borrador
    initial: {!! isset($borrador) ? json_encode($borrador->payload, JSON_UNESCAPED_UNICODE) : 'null' !!},
    borradorId: {!! isset($borrador) ? (int) $borrador->id : 'null' !!}
  })'
  x-init="init()"
  class="max-w-6xl mx-auto px-4 py-6 space-y-6"
>

  <div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Nueva factura</h1>
    <div class="flex gap-2">
      <button class="btn bg-gray-100 hover:opacity-90" @click="guardarBorrador()">Guardar borrador</button>
      <button class="btn bg-violet-600 hover:bg-violet-700 text-white" @click="previsualizar()">Previsualizar</button>
    </div>
  </div>

  {{-- Datos del comprobante --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Tipo de comprobante</label>
        <select x-model="form.tipo_comprobante" @change="onTipoComprobanteChange" class="form-select w-full">
          <option value="I">Ingreso</option>
          <option value="E">Egreso</option>
        </select>
      </div>

      <div>
        <label class="block text-sm text-gray-600 mb-1">Serie</label>
        <input type="text" class="form-input w-full" x-model="form.serie" readonly>
      </div>

      <div>
        <label class="block text-sm text-gray-600 mb-1">Folio</label>
        <div class="flex gap-2">
          <input type="text" class="form-input w-full" x-model="form.folio" readonly>
          <button class="btn bg-gray-100 hover:opacity-90" type="button" @click="pedirSiguienteFolio()">↻</button>
        </div>
      </div>

      <div>
        <label class="block text-sm text-gray-600 mb-1">Fecha</label>
        <input type="datetime-local" class="form-input w-full" x-model="form.fecha" min="{{ $minFecha }}" max="{{ $maxFecha }}">
      </div>

      {{-- Método de pago (SAT) --}}
      <div>
        <label class="block text-sm text-gray-600 mb-1">Método de pago</label>
        <select x-model="form.metodo_pago" class="form-select w-full">
          @foreach($metodosPago as $m)
            <option value="{{ is_array($m) ? $m['clave'] : $m->clave }}">
              {{ is_array($m) ? $m['clave'] : $m->clave }} — {{ is_array($m) ? $m['descripcion'] : $m->descripcion }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- Forma de pago (SAT) --}}
      <div>
        <label class="block text-sm text-gray-600 mb-1">Forma de pago</label>
        <select x-model="form.forma_pago" class="form-select w-full">
          @foreach($formasPago as $f)
            <option value="{{ is_array($f) ? $f['clave'] : $f->clave }}">
              {{ is_array($f) ? $f['clave'] : $f->clave }} — {{ is_array($f) ? $f['descripcion'] : $f->descripcion }}
            </option>
          @endforeach
        </select>
      </div>
    </div>

    <div>
      <label class="block text-sm text-gray-600 mb-1">Comentarios (PDF)</label>
      <textarea class="form-textarea w-full" rows="2" x-model="form.comentarios_pdf"></textarea>
    </div>

    {{-- Documentos Relacionados (simple capturador) --}}
    <div class="space-y-2">
      <div class="flex items-center justify-between">
        <label class="block text-sm text-gray-600">Documentos Relacionados</label>
        <button class="btn-xs bg-gray-100 hover:opacity-90" @click="agregarRelacionado()">+ Agregar</button>
      </div>
      <template x-for="(rel, i) in form.relacionados" :key="i">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
          <input class="form-input" placeholder="Tipo relación (ej. 01, 04)" x-model="rel.tipo_relacion">
          <input class="form-input sm:col-span-2" placeholder="UUIDs (separados por coma)" x-model="rel.uuids">
        </div>
      </template>
    </div>
  </div>

  {{-- Datos del cliente --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="flex items-center justify-between mb-3">
      <div class="text-sm text-gray-600">Cliente</div>
      <button class="btn-xs bg-gray-100 hover:opacity-90" @click="$dispatch('open-modal','modalEditarCliente')">Editar</button>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <select class="form-select w-full" x-model="form.cliente_id" @change="onClienteChange">
          <option value="">— Selecciona —</option>
          <template x-for="c in clientes" :key="c.id">
            <option :value="c.id" x-text="`${c.razon_social} — ${c.rfc}`"></option>
          </template>
        </select>
      </div>
      <div class="sm:col-span-2">
        <div class="text-sm text-gray-700" x-text="clienteSel?.razon_social || '—'"></div>
        <div class="text-xs text-gray-500">RFC: <span x-text="clienteSel?.rfc || ''"></span></div>
        <div class="text-xs text-gray-500" x-text="direccionCliente()"></div>
        <div class="text-xs text-gray-500" x-text="clienteSel?.email || ''"></div>
      </div>
    </div>
  </div>

  {{-- Conceptos --}}
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4 space-y-3">
    <div class="flex items-center justify-between">
      <div class="text-sm text-gray-600">Conceptos</div>
      <div class="flex gap-2">
        <input class="form-input" placeholder="Buscar producto..." x-model="buscador.q" @input.debounce.300ms="buscarProductos">
        <div class="relative">
          <div class="absolute z-20 bg-white dark:bg-gray-800 rounded shadow w-72 max-h-64 overflow-auto" x-show="buscador.items.length">
            <template x-for="p in buscador.items" :key="p.id">
              <button type="button" class="block w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-900"
                      @click="agregarDesdeProducto(p)">
                <div class="font-medium" x-text="p.descripcion"></div>
                <div class="text-xs text-gray-500">Clave: <span x-text="p.clave"></span> — SAT: <span x-text="p.clave_prod_serv"></span> / <span x-text="p.clave_unidad"></span></div>
              </button>
            </template>
          </div>
        </div>
        <button class="btn bg-gray-100 hover:opacity-90" type="button" @click="agregarConcepto()">+ Agregar vacío</button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="table-auto w-full text-sm">
        <thead class="text-xs uppercase text-gray-500 border-b">
          <tr>
            <th class="px-2 py-2">Descripción</th>
            <th class="px-2 py-2 w-28">Cantidad</th>
            <th class="px-2 py-2 w-28">Precio</th>
            <th class="px-2 py-2 w-28">Desc.</th>
            <th class="px-2 py-2 w-36">Clave ProdServ</th>
            <th class="px-2 py-2 w-32">Clave Unidad</th>
            <th class="px-2 py-2 w-24">Impuestos</th>
            <th class="px-2 py-2 w-16"></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="(c, idx) in form.conceptos" :key="c.uid">
            <tr class="border-b border-gray-100">
              <td class="px-2 py-2 align-top">
                <textarea class="form-textarea w-full" rows="2" x-model="c.descripcion"></textarea>
              </td>
              <td class="px-2 py-2 align-top">
                <input type="number" step="0.0001" class="form-input w-full text-right" x-model.number="c.cantidad" @input="recalcularTotales">
              </td>
              <td class="px-2 py-2 align-top">
                <input type="number" step="0.01" class="form-input w-full text-right" x-model.number="c.precio" @input="recalcularTotales">
              </td>
              <td class="px-2 py-2 align-top">
                <input type="number" step="0.01" class="form-input w-full text-right" x-model.number="c.descuento" @input="recalcularTotales">
              </td>
              <td class="px-2 py-2 align-top">
                <div class="flex gap-1">
                  <input class="form-input w-full" x-model="c.clave_prod_serv">
                  <button class="btn-xs bg-gray-100 hover:opacity-90" type="button" @click="$dispatch('open-sat',{idx:idx,tipo:'prodserv'})">…</button>
                </div>
              </td>
              <td class="px-2 py-2 align-top">
                <div class="flex gap-1">
                  <input class="form-input w-full" x-model="c.clave_unidad">
                  <button class="btn-xs bg-gray-100 hover:opacity-90" type="button" @click="$dispatch('open-sat',{idx:idx,tipo:'unidad'})">…</button>
                </div>
              </td>
              <td class="px-2 py-2 align-top">
                <button class="btn-xs bg-gray-100 hover:opacity-90" type="button" @click="$dispatch('open-impuestos',{idx:idx})">Editar</button>
              </td>
              <td class="px-2 py-2 align-top">
                <button class="btn-xs text-red-500 hover:text-red-600" type="button" @click="eliminarConcepto(idx)">✕</button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <div class="flex justify-end">
      <div class="w-full max-w-sm space-y-1 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span x-text="money(totales.subtotal)"></span></div>
        <div class="flex justify-between"><span class="text-gray-500">Descuento</span><span x-text="money(totales.descuento)"></span></div>
        <div class="flex justify-between"><span class="text-gray-500">Impuestos</span><span x-text="money(totales.impuestos)"></span></div>
        <div class="flex justify-between font-semibold text-gray-700"><span>Total</span><span x-text="money(totales.total)"></span></div>
      </div>
    </div>
  </div>

  {{-- Forms ocultos --}}
  <form x-ref="previewForm" action="{{ route('facturas.preview') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="payload" :value="JSON.stringify(form)">
  </form>

  <form x-ref="guardarForm" action="{{ route('facturas.guardar') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="payload" :value="JSON.stringify(form)">
  </form>

  {{-- Drawer: Editar cliente --}}
  <div x-data="{open:false}" x-ref="drawerCliente"
       x-on:open-modal.window="if($event.detail==='modalEditarCliente') open=true"
       x-show="open" x-transition.opacity class="fixed inset-0 z-40" style="display:none">
    <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-gray-900 shadow-xl z-50 overflow-y-auto"
         @click.stop
         x-transition:enter="transform transition ease-in-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         @keydown.escape.window="open=false">
      <div class="p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">Editar cliente</h3>
          <button class="text-gray-500 hover:text-gray-700" @click="open=false">✕</button>
        </div>

        <template x-if="clienteSel && form.cliente_id">
          <form @submit.prevent="submitEditarCliente">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div><label class="text-xs text-gray-500">Razón social</label><input class="form-input w-full" x-model="clienteEdit.razon_social"></div>
              <div><label class="text-xs text-gray-500">RFC</label><input class="form-input w-full" x-model="clienteEdit.rfc"></div>
              <div class="sm:col-span-2"><label class="text-xs text-gray-500">Correo</label><input type="email" class="form-input w-full" x-model="clienteEdit.email"></div>
              <div><label class="text-xs text-gray-500">Calle</label><input class="form-input w-full" x-model="clienteEdit.calle"></div>
              <div><label class="text-xs text-gray-500">No. ext</label><input class="form-input w-full" x-model="clienteEdit.no_ext"></div>
              <div><label class="text-xs text-gray-500">No. int</label><input class="form-input w-full" x-model="clienteEdit.no_int"></div>
              <div><label class="text-xs text-gray-500">Colonia</label><input class="form-input w-full" x-model="clienteEdit.colonia"></div>
              <div><label class="text-xs text-gray-500">Localidad</label><input class="form-input w-full" x-model="clienteEdit.localidad"></div>
              <div><label class="text-xs text-gray-500">Estado</label><input class="form-input w-full" x-model="clienteEdit.estado"></div>
              <div><label class="text-xs text-gray-500">CP</label><input class="form-input w-full" x-model="clienteEdit.codigo_postal"></div>
              <div class="sm:col-span-2"><label class="text-xs text-gray-500">País</label><input class="form-input w-full" x-model="clienteEdit.pais"></div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
              <button type="button" class="btn bg-gray-100 dark:bg-gray-700" @click="open=false">Cancelar</button>
              <button type="submit" class="btn bg-violet-600 hover:bg-violet-700 text-white">Guardar</button>
            </div>
          </form>
        </template>

        <template x-if="!form.cliente_id">
          <div class="text-sm text-gray-500">Primero selecciona un cliente.</div>
        </template>
      </div>
    </div>
  </div>

  {{-- Drawer: Impuestos por concepto --}}
  <div x-data="{open:false}"
       x-on:open-impuestos.window="open=true; modalImpuestos.idx = $event.detail.idx; cargarImpuestosEdit()"
       x-show="open" x-transition.opacity class="fixed inset-0 z-40" style="display:none">
    <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-lg bg-white dark:bg-gray-900 shadow-xl z-50 overflow-y-auto"
         @click.stop
         x-transition:enter="transform transition ease-in-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         @keydown.escape.window="open=false">
      <div class="p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold">
            Impuestos del concepto #<span x-text="(modalImpuestos.idx+1)"></span>
          </h3>
          <button class="text-gray-500 hover:text-gray-700" @click="open=false">✕</button>
        </div>

        <div class="space-y-2">
          <template x-for="(imp, i) in impuestosEdit" :key="imp.uid">
            <div class="border rounded-lg p-3 space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <label class="text-xs text-gray-500">Tipo</label>
                  <select class="form-select w-full" x-model="imp.tipo">
                    <option value="T">Traslado</option>
                    <option value="R">Retención</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs text-gray-500">Impuesto</label>
                  <select class="form-select w-full" x-model="imp.impuesto">
                    <option value="IVA">IVA</option>
                    <option value="ISR">ISR</option>
                    <option value="IEPS">IEPS</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs text-gray-500">Factor</label>
                  <select class="form-select w-full" x-model="imp.factor">
                    <option value="Tasa">Tasa</option>
                    <option value="Cuota">Cuota</option>
                    <option value="Exento">Exento</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs text-gray-500">Tasa/Cuota (%)</label>
                  <input type="number" step="0.0001" class="form-input w-full" x-model.number="imp.tasa">
                </div>
              </div>

              <div class="flex justify-between items-center">
                <div class="text-xs text-gray-500">
                  Base: <span x-text="money(baseRow(form.conceptos[modalImpuestos.idx]))"></span>
                </div>
                <button type="button" class="btn-xs text-red-500 hover:text-red-600" @click="eliminarImpuesto(i)">Eliminar</button>
              </div>
            </div>
          </template>

          <button type="button" class="btn-sm bg-gray-100 dark:bg-gray-700 hover:opacity-90" @click="agregarImpuesto">+ Agregar impuesto</button>
        </div>

        <div class="flex justify-end gap-2 mt-4">
          <button type="button" class="btn bg-gray-100 dark:bg-gray-700" @click="open=false">Cancelar</button>
          <button type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white" @click="guardarImpuestos(); open=false">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Drawer: Claves SAT --}}
  <div x-data="{open:false}"
       x-on:open-sat.window="open=true; satModal.idx = $event.detail.idx; satModal.tipo = $event.detail.tipo; satModal.q=''; satModal.items=[];"
       x-show="open" x-transition.opacity class="fixed inset-0 z-40" style="display:none">
    <div class="absolute inset-0 bg-black/40" @click="open=false"></div>
    <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl z-50 overflow-y-auto"
         @click.stop
         x-transition:enter="transform transition ease-in-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         @keydown.escape.window="open=false">
      <div class="p-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold" x-text="satModal.tipo==='prodserv' ? 'Buscar Clave ProdServ' : 'Buscar Clave Unidad'"></h3>
          <button class="text-gray-500 hover:text-gray-700" @click="open=false">✕</button>
        </div>

        <div class="flex gap-2 mb-3">
          <input class="form-input w-full" placeholder="Escribe al menos 3 caracteres" x-model="satModal.q"
                 @input.debounce.300ms="buscarSat()">
        </div>

        <div class="border rounded-lg divide-y">
          <template x-for="it in satModal.items" :key="it.id">
            <button type="button" class="w-full text-left p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    @click="aplicarSat(it); open=false">
              <div class="font-medium" x-text="`${it.clave} — ${it.descripcion}`"></div>
            </button>
          </template>
          <div class="p-3 text-sm text-gray-500" x-show="satModal.items.length===0">Sin resultados</div>
        </div>

        <div class="flex justify-end mt-4">
          <button type="button" class="btn bg-gray-100 dark:bg-gray-700" @click="open=false">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- Scripts --}}
<script>
window.facturaForm = (opts) => ({
  opts,
  clientes: opts.clientes || [],
  clienteSel: null,
  clienteEdit: {},

  buscador: { q:'', items:[] },

  form: {
    tipo_comprobante: 'I',
    serie: '',
    folio: '',
    fecha: new Date().toISOString().slice(0,16),
    metodo_pago: 'PUE',
    forma_pago: '03',
    comentarios_pdf: '',
    relacionados: [],
    cliente_id: '',
    conceptos: [],
    borrador_id: null,
  },

  totales: { subtotal:0, descuento:0, impuestos:0, total:0 },

  init(){
    this.pedirSiguienteFolio();
    this.agregarConcepto();
    if (opts?.initial) {
      this.form = Object.assign(this.form, opts.initial || {});
      if (opts?.borradorId) this.form.borrador_id = opts.borradorId;
      this.onClienteChange();
      this.recalcularTotales();
    }
  },

  money(n){ return Number(n||0).toFixed(2); },

  direccionCliente(){
    if (!this.clienteSel) return '';
    const c = this.clienteSel;
    const p1 = [c.calle, c.no_ext, c.no_int?('Int '+c.no_int):null].filter(Boolean).join(' ');
    const p2 = [c.colonia, c.localidad, c.estado].filter(Boolean).join(', ');
    const p3 = c.codigo_postal || '';
    return [p1, p2, p3].filter(Boolean).join(' — ');
  },

  onClienteChange(){
    const id = Number(this.form.cliente_id);
    this.clienteSel = this.clientes.find(x => Number(x.id)===id) || null;
    if (this.clienteSel) {
      this.clienteEdit = JSON.parse(JSON.stringify(this.clienteSel));
    } else {
      this.clienteEdit = {};
    }
  },

  async submitEditarCliente(){
    if (!this.form.cliente_id) return;
    const url = `${this.opts.routeClienteUpdateBase}/${this.form.cliente_id}/quick-update`;
    const body = new URLSearchParams();
    body.append('_token', this.opts.csrf);
    body.append('_method','PUT');
    for (const [k,v] of Object.entries(this.clienteEdit)) body.append(k, v ?? '');

    const r = await fetch(url, { method:'POST', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}, body });
    if (!r.ok) { alert('No se pudo actualizar el cliente'); return; }
    const j = await r.json().catch(()=>null);
    if (j && j.id){
      const i = this.clientes.findIndex(x => Number(x.id)===Number(j.id));
      if (i>=0) this.clientes.splice(i,1,j);
      this.onClienteChange();
    } else {
      this.onClienteChange();
    }
    const drawer = this.$refs.drawerCliente;
    if (drawer && drawer.__x) drawer.__x.$data.open = false;
  },

  onTipoComprobanteChange(){ this.pedirSiguienteFolio(); },

  pedirSiguienteFolio(){
    const url = `${this.opts.apiSeriesNext}?tipo=${encodeURIComponent(this.form.tipo_comprobante)}`;
    fetch(url).then(r=>r.json()).then(j=>{
      this.form.serie = j.serie || '';
      this.form.folio = (j.folio != null ? j.folio : j.siguiente) || '';
    }).catch(()=>{});
  },

  async buscarProductos(){
    const q = this.buscador.q.trim();
    if (q.length < 2) { this.buscador.items = []; return; }
    const url = `${this.opts.apiProductosBuscar}?q=${encodeURIComponent(q)}`;
    const r = await fetch(url);
    this.buscador.items = await r.json().catch(()=>[]);
  },

  agregarDesdeProducto(p){
    this.form.conceptos.push({
      uid: crypto.randomUUID(),
      descripcion: p.descripcion,
      cantidad: 1,
      precio: Number(p.precio || 0),
      descuento: 0,
      clave_prod_serv: p.clave_prod_serv || '',
      clave_unidad: p.clave_unidad || '',
      unidad: p.unidad || '',
      impuestos: [],
    });
    this.buscador.q = ''; this.buscador.items = [];
    this.recalcularTotales();
  },

  agregarConcepto(){
    this.form.conceptos.push({
      uid: crypto.randomUUID(),
      descripcion: '',
      cantidad: 1,
      precio: 0,
      descuento: 0,
      clave_prod_serv: '',
      clave_unidad: '',
      unidad: '',
      impuestos: [],
    });
    this.recalcularTotales();
  },

  eliminarConcepto(i){
    this.form.conceptos.splice(i,1);
    this.recalcularTotales();
  },

  baseRow(c){
    const sub = (Number(c.cantidad||0) * Number(c.precio||0));
    const des = Number(c.descuento||0);
    return Math.max(sub - des, 0);
  },

  recalcularTotales(){
    let subtotal=0, descuento=0, impuestos=0;
    for (const c of this.form.conceptos) {
      const sub = (Number(c.cantidad||0) * Number(c.precio||0));
      const des = Number(c.descuento||0);
      const base = Math.max(sub - des, 0);
      subtotal += sub; descuento += des;
      for (const i of (c.impuestos||[])) {
        if ((i.factor||'') === 'Exento') continue;
        const tasa = Number(i.tasa||0) / 100;
        const m = base * tasa;
        impuestos += (i.tipo==='R') ? -m : m;
      }
    }
    this.totales = {
      subtotal: Number(subtotal.toFixed(2)),
      descuento: Number(descuento.toFixed(2)),
      impuestos: Number(impuestos.toFixed(2)),
      total: Number((subtotal - descuento + impuestos).toFixed(2)),
    };
  },

  previsualizar(){
    if (!this.form.cliente_id) { alert('Selecciona un cliente'); return; }
    if (!this.form.serie || !this.form.folio) { alert('Serie/Folio inválidos'); return; }
    if (!this.form.conceptos.length) { alert('Agrega al menos un concepto'); return; }
    // normaliza relacionados para el back (acepta uuids separados por coma)
    this.form.relacionados = (this.form.relacionados||[]).map(r => {
      const uu = typeof r.uuids === 'string'
        ? r.uuids.split(/[,;\s]+/).filter(Boolean)
        : (Array.isArray(r.uuids) ? r.uuids : []);
      return { tipo_relacion: r.tipo_relacion || '', uuids: uu };
    });
    this.$refs.previewForm.submit();
  },

  guardarBorrador(){
    if (!this.form.cliente_id) { alert('Selecciona un cliente'); return; }
    if (!this.form.serie || !this.form.folio) { alert('Serie/Folio inválidos'); return; }
    if (!this.form.conceptos.length) { alert('Agrega al menos un concepto'); return; }
    this.$refs.guardarForm.submit();
  },

  // SAT modal helpers
  satModal: { idx:null, tipo:null, q:'', items:[] },
  async buscarSat(){
    const tipo = this.satModal.tipo;
    const q = this.satModal.q.trim();
    if (q.length < 3) { this.satModal.items=[]; return; }
    const url = tipo==='prodserv' ? this.opts.apiSatProdServ : this.opts.apiSatUnidad;
    const r = await fetch(`${url}?q=${encodeURIComponent(q)}`);
    this.satModal.items = await r.json().catch(()=>[]);
  },
  aplicarSat(it){
    const c = this.form.conceptos[this.satModal.idx];
    if (!c) return;
    if (this.satModal.tipo==='prodserv') c.clave_prod_serv = it.clave;
    else c.clave_unidad = it.clave;
  },

  // Impuestos modal
  modalImpuestos: { idx:null },
  impuestosEdit: [],
  cargarImpuestosEdit(){
    const c = this.form.conceptos[this.modalImpuestos.idx];
    this.impuestosEdit = JSON.parse(JSON.stringify(c?.impuestos || []));
  },
  agregarImpuesto(){
    this.impuestosEdit.push({ uid: crypto.randomUUID(), tipo:'T', impuesto:'IVA', factor:'Tasa', tasa:16 });
  },
  eliminarImpuesto(i){ this.impuestosEdit.splice(i,1); },
  guardarImpuestos(){
    const c = this.form.conceptos[this.modalImpuestos.idx];
    if (!c) return;
    c.impuestos = JSON.parse(JSON.stringify(this.impuestosEdit));
    this.recalcularTotales();
  },

  agregarRelacionado(){
    (this.form.relacionados ||= []).push({ tipo_relacion:'', uuids:'' });
  },
});
</script>
@endsection


@push('scripts')
<script>
  window.facturaForm = (opts) => ({
    // ----- estado -----
    form: {
      tipo_comprobante: 'I', // default
      serie: '',
      folio: '',
      fecha: opts.maxFecha, // por defecto "ahora"
      metodo_pago: 'PUE',
      forma_pago: '03',     // Transferencia por default
      comentarios_pdf: '',
      cliente_id: '',
      conceptos: [],
      relacionados: [],
    },
    clientes: opts.clientes || [],
    clienteSel: {},
    clienteEdit: {},

    buscaProd: { query: '', suggestions: [], selectedId: '' },

    totales: { subtotal: 0, descuento: 0, impuestos: 0, total: 0 },

    modalImpuestos: { open:false, idx:-1 },
    impuestosEdit: [],
    satModal: { open:false, idx:-1, tipo:'prodserv', q:'', items:[] },

    // ----- helpers -----
    uid(){ return Math.random().toString(36).slice(2); },
    money(n){ n = Number(n||0); return n.toLocaleString('es-MX',{style:'currency',currency:'MXN'}); },

    baseRow(r){
      const sub = Number(r.cantidad||0) * Number(r.precio||0);
      const des = Number(r.descuento||0);
      return Math.max(sub - des, 0);
    },
    importeRow(r){
      const base = this.baseRow(r);
      let imp = 0;
      for (const i of (r.impuestos||[])) {
        if (i.factor==='Exento') continue;
        const tasa = Number(i.tasa||0)/100;
        const monto = base * tasa;
        imp += (i.tipo==='R' ? -monto : monto);
      }
      return base + imp;
    },
    resumenImpuestos(r){
      const xs = (r.impuestos||[]).map(i => `${i.tipo==='R'?'Ret':'Tras'} ${i.impuesto} ${i.factor==='Exento'?'0%':(Number(i.tasa||0).toFixed(2)+'%')}`);
      return xs.join(', ');
    },

    // ----- init -----
    init(){
      // Serie/Folio para Ingreso como default:
      this.pedirSiguienteFolio();
      // al menos una fila de concepto para que veas inputs
      this.agregarConcepto();
    },

    // ----- comprobante -----
    onTipoComprobanteChange(){ this.pedirSiguienteFolio(); },
    pedirSiguienteFolio(){
    const url = `${opts.apiSeriesNext}?tipo=${encodeURIComponent(this.form.tipo_comprobante)}`;
    fetch(url)
        .then(r=>r.json())
        .then(j=>{
        this.form.serie = j.serie || '';
        this.form.folio = (j.folio != null ? j.folio : j.siguiente) || '';
        })
        .catch(()=>{});
    },
    clampFecha(){
      if (!this.form.fecha) { this.form.fecha = this.maxFecha; return; }
      if (this.form.fecha < this.minFecha) this.form.fecha = this.minFecha;
      if (this.form.fecha > this.maxFecha) this.form.fecha = this.maxFecha;
    },

    // ----- cliente -----
    onClienteChange(){
      const c = this.clientes.find(x => Number(x.id) === Number(this.form.cliente_id));
      this.clienteSel = c || {};
      this.clienteEdit = JSON.parse(JSON.stringify(this.clienteSel || {}));
    },
    async submitEditarCliente(){
      if (!this.form.cliente_id) return;
      const url = `${opts.routeClienteUpdateBase}/${this.form.cliente_id}`;
      const body = new URLSearchParams();
      body.append('_token', opts.csrf);
      body.append('_method','PUT');
      for (const [k,v] of Object.entries(this.clienteEdit)) body.append(k, v ?? '');
      const r = await fetch(url, { method:'POST', headers:{'Accept':'application/json'}, body });
      if (!r.ok) { alert('No se pudo actualizar el cliente'); return; }
      const j = await r.json().catch(()=>null);
      if (j && j.id){
        // actualiza en la lista local
        const i = this.clientes.findIndex(x => Number(x.id)===Number(j.id));
        if (i>=0) this.clientes.splice(i,1,j);
        this.onClienteChange();
      } else {
        // si el controlador respondió redirect/html, al menos refrescamos la UI local
        this.onClienteChange();
      }
      // cierra drawer
      document.querySelector('[x-ref=drawerCliente]')?.classList.add('hidden');
    },

    // ----- conceptos -----
    agregarConcepto(){
      this.form.conceptos.push({
        uid: this.uid(),
        descripcion: '',
        clave_prod_serv: '',
        clave_unidad: '',
        unidad: '',
        cantidad: 1,
        precio: 0,
        descuento: 0,
        impuestos: [],
      });
      this.recalcularTotales();
    },
    eliminarConcepto(i){
      this.form.conceptos.splice(i,1);
      this.recalcularTotales();
    },

    // buscador externo
    async buscarProductoGlobal(){
      const q = (this.buscaProd.query||'').trim();
      if (q.length < 2) { this.buscaProd.suggestions = []; return; }
      const url = `${opts.apiProductosBuscar}?q=${encodeURIComponent(q)}&rfc=${encodeURIComponent(opts.rfcUsuarioId)}`;
      const r = await fetch(url);
      const list = await r.json().catch(()=>[]);
      this.buscaProd.suggestions = list || [];
    },
    agregarProductoDesdeBuscador(){
      const id = Number(this.buscaProd.selectedId||0);
      const p = (this.buscaProd.suggestions||[]).find(x => Number(x.id)===id);
      if (!p) return;
      const row = {
        uid: this.uid(),
        descripcion: p.descripcion || '',
        clave_prod_serv: p.clave_prod_serv || '',
        clave_unidad: p.clave_unidad || '',
        unidad: p.unidad || '',
        cantidad: 1,
        precio: Number(p.precio || 0),
        descuento: 0,
        impuestos: [],
      };
      this.form.conceptos.push(row);
      this.buscaProd.selectedId = '';
      this.recalcularTotales();
    },

    // impuestos modal
    cargarImpuestosEdit(){
      const idx = this.modalImpuestos.idx;
      const row = this.form.conceptos[idx]; if (!row) return;
      this.impuestosEdit = (row.impuestos || []).map(i => ({...i})) // clone
      if (!this.impuestosEdit.length) this.agregarImpuesto();
    },
    agregarImpuesto(){
      this.impuestosEdit.push({ uid:this.uid(), tipo:'T', impuesto:'IVA', factor:'Tasa', tasa:16 });
    },
    eliminarImpuesto(i){ this.impuestosEdit.splice(i,1); },
    guardarImpuestos(){
      const idx = this.modalImpuestos.idx;
      if (idx<0) return;
      // limpieza simple: tasa >=0
      for (const it of this.impuestosEdit){ it.tasa = Math.max(Number(it.tasa||0), 0); }
      this.form.conceptos[idx].impuestos = this.impuestosEdit.map(i => ({...i}));
      this.cerrarImpuestos();
      this.recalcularTotales();
    },
    cerrarImpuestos(){ this.modalImpuestos.open=false; this.modalImpuestos.idx=-1; this.impuestosEdit = []; },

    // SAT modal
    async buscarSat(){
      const q = (this.satModal.q||'').trim();
      if (q.length < 3) { this.satModal.items = []; return; }
      const isProd = this.satModal.tipo === 'prodserv';
      const url = (isProd ? opts.apiSatProdServ : opts.apiSatUnidad) + `?q=${encodeURIComponent(q)}`;
      const r = await fetch(url);
      const list = await r.json().catch(()=>[]);
      this.satModal.items = list || [];
    },
    aplicarSat(it){
      const idx = this.satModal.idx;
      const row = this.form.conceptos[idx]; if (!row) return;
      if (this.satModal.tipo==='prodserv') row.clave_prod_serv = it.clave || '';
      else if (this.satModal.tipo==='unidad'){ row.clave_unidad = it.clave || ''; row.unidad = it.unidad || row.unidad; }
      this.satModal.open=false;
    },

    // totales
    recalcularTotales(){
      let subtotal=0, descuento=0, impuestos=0;
      for (const r of this.form.conceptos){
        const base = this.baseRow(r);
        subtotal += Number(r.cantidad||0) * Number(r.precio||0);
        descuento += Number(r.descuento||0);
        // impuestos por concepto
        for (const i of (r.impuestos||[])){
          if (i.factor==='Exento') continue;
          const tasa = Number(i.tasa||0)/100;
          const m = base * tasa;
          impuestos += (i.tipo==='R' ? -m : m);
        }
      }
      const total = subtotal - descuento + impuestos;
      this.totales = { subtotal, descuento, impuestos, total };
    },

    // relacionados
    agregarRelacionado(){ this.form.relacionados.push({ uid:this.uid(), tipo_relacion:'', uuid:'' }); },
    eliminarRelacionado(i){ this.form.relacionados.splice(i,1); },

    // acciones
    previsualizar(){
      // validación mínima en cliente
      if (!this.form.cliente_id) { alert('Selecciona un cliente'); return; }
      if (!this.form.serie || !this.form.folio) { alert('Serie/Folio inválidos'); return; }
      if (!this.form.conceptos.length) { alert('Agrega al menos un concepto'); return; }
      // post al preview (servidor hará validación completa)
      this.$refs.previewForm.submit();
    },
    guardarBorrador(){
      alert('El guardado definitivo se hará desde la Previsualización, para obligar la validación previa.');
      this.previsualizar();
    },
  });

</script>
@endpush
