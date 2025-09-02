@extends('layouts.app')

@section('title','Nueva Factura')

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

  {{-- Encabezado + RFC activo --}}
  <div class="flex items-start justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>
    <div class="flex items-center gap-2">
      <span class="text-xs uppercase text-gray-400 dark:text-gray-500">RFC activo</span>
      <div class="px-2 py-1 rounded bg-violet-500/10 text-violet-600 dark:text-violet-400 text-sm">
        {{ $rfcActivo ?? '—' }}
      </div>
    </div>
  </div>

  @php
    // Fallbacks desde el servidor:
    $rfcUsuarioId = $rfcUsuarioId ?? (session('rfc_usuario_id') ?? session('rfc_activo_id') ?? 0);

    // serializa clientes con los campos que pediste
    $clientesJson = ($clientes ?? collect())->map(function ($c) {
      return [
        'id'            => $c->id,
        'rfc'           => $c->rfc,
        'razon_social'  => $c->razon_social,
        'calle'         => $c->calle,
        'no_ext'        => $c->no_ext,
        'no_int'        => $c->no_int,
        'colonia'       => $c->colonia,
        'localidad'     => $c->localidad,
        'estado'        => $c->estado,
        'codigo_postal' => $c->codigo_postal,
        'pais'          => $c->pais,
        'email'         => $c->email,
      ];
    })->values()->toJson(JSON_UNESCAPED_UNICODE);

    // Ventana SAT: 72h hacia atrás, máximo "ahora"
    $minFecha = $minFecha ?? now()->copy()->subHours(72)->format('Y-m-d\TH:i');
    $maxFecha = $maxFecha ?? now()->format('Y-m-d\TH:i');
  @endphp

  <div
    x-data='facturaForm({
      rfcUsuarioId: {{ (int) $rfcUsuarioId }},
      clientes: {!! $clientesJson !!},
      minFecha: "{{ $minFecha }}",
      maxFecha: "{{ $maxFecha }}",
      apiSeriesNext: "{{ url('/api/series/next') }}",
      apiProductosBuscar: "{{ url('/api/productos/buscar') }}",
      routeClienteUpdateBase: "{{ url('/catalogos/clientes') }}",
      csrf: "{{ csrf_token() }}"
    })'
    class="space-y-6"
  >
    {{-- DATOS DEL COMPROBANTE --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Datos del comprobante</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Tipo comprobante --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de comprobante</label>
          <select x-model="form.tipo_comprobante" @change="onTipoComprobanteChange" class="form-select w-full">
            <option value="I">Ingreso (Factura/Honorarios/Arrendamiento)</option>
            <option value="E">Egreso (Nota de crédito)</option>
            <option value="T">Traslado</option>
            <option value="N">Nómina</option>
            <option value="P">Pago</option>
          </select>
        </div>

        {{-- Serie (solo lectura, autollenada) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serie</label>
          <input type="text" x-model="form.serie" class="form-input w-full" readonly>
        </div>

        {{-- Folio (solo lectura, autollenado) --}}
        <div>
          <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Folio</label>
            <button type="button" class="text-xs text-violet-600 hover:text-violet-700" @click="pedirSiguienteFolio">Actualizar</button>
          </div>
          <input type="text" x-model="form.folio" class="form-input w-full" readonly>
        </div>

        {{-- Fecha (clamp 72h -> ahora) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha y hora</label>
          <input type="datetime-local" class="form-input w-full" :min="minFecha" :max="maxFecha" x-model="form.fecha" @change="clampFecha">
          <p class="text-xs text-gray-500 mt-1">La fecha debe estar dentro de las últimas 72 horas.</p>
        </div>

        {{-- Método de pago --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Método de pago</label>
          <select x-model="form.metodo_pago" class="form-select w-full">
            <option value="PUE">PUE</option>
            <option value="PPD">PPD</option>
          </select>
        </div>

        {{-- Forma de pago (select) --}}
        <div>
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Forma de pago</label>
          <select x-model="form.forma_pago" class="form-select w-full">
            <option value="01">01 - Efectivo</option>
            <option value="02">02 - Cheque</option>
            <option value="03">03 - Transferencia</option>
            <option value="04">04 - Tarjeta de crédito</option>
            <option value="28">28 - Tarjeta de débito</option>
            <option value="99">99 - Por definir</option>
          </select>
        </div>

        {{-- Comentarios PDF (textarea) --}}
        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comentarios en PDF</label>
          <textarea x-model="form.comentarios_pdf" rows="3" class="form-textarea w-full" placeholder="Notas visibles en el PDF"></textarea>
        </div>
      </div>
    </div>

    {{-- CLIENTE --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Cliente</h2>
        <div class="flex items-center gap-2">
          <div class="w-64">
            <select x-model.number="form.cliente_id" @change="onClienteChange" class="form-select w-full">
              <option value="">— Selecciona —</option>
              <template x-for="c in clientes" :key="c.id">
                <option :value="c.id" x-text="`${c.razon_social} — ${c.rfc}`"></option>
              </template>
            </select>
          </div>
          <button type="button" class="btn-sm bg-violet-500 hover:bg-violet-600 text-white" :disabled="!form.cliente_id" @click="$dispatch('open-modal','modalEditarCliente')">Actualizar</button>
        </div>
      </div>

      {{-- Labels limpios (sin card dentro de card) --}}
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-1 gap-x-6 text-sm">
        <div><span class="text-gray-400">Razón social:</span> <span class="font-medium" x-text="clienteSel.razon_social || '—'"></span></div>
        <div><span class="text-gray-400">RFC:</span> <span class="font-medium" x-text="clienteSel.rfc || '—'"></span></div>
        <div><span class="text-gray-400">Correo:</span> <span class="font-medium" x-text="clienteSel.email || '—'"></span></div>
        <div><span class="text-gray-400">Calle:</span> <span class="font-medium" x-text="clienteSel.calle || '—'"></span></div>
        <div><span class="text-gray-400">No. ext:</span> <span class="font-medium" x-text="clienteSel.no_ext || '—'"></span></div>
        <div><span class="text-gray-400">No. int:</span> <span class="font-medium" x-text="clienteSel.no_int || '—'"></span></div>
        <div><span class="text-gray-400">Colonia:</span> <span class="font-medium" x-text="clienteSel.colonia || '—'"></span></div>
        <div><span class="text-gray-400">Localidad:</span> <span class="font-medium" x-text="clienteSel.localidad || '—'"></span></div>
        <div><span class="text-gray-400">Estado:</span> <span class="font-medium" x-text="clienteSel.estado || '—'"></span></div>
        <div><span class="text-gray-400">País:</span> <span class="font-medium" x-text="clienteSel.pais || '—'"></span></div>
        <div><span class="text-gray-400">C.P.:</span> <span class="font-medium" x-text="clienteSel.codigo_postal || '—'"></span></div>
      </div>
    </div>

    {{-- CONCEPTOS --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Conceptos</h2>
        <button type="button" class="btn-sm bg-gray-900 dark:bg-white dark:text-gray-900 text-white hover:opacity-90" @click="agregarConcepto">Agregar concepto</button>
      </div>

      <div class="overflow-x-auto">
        <table class="table-auto w-full text-sm">
          <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700/60">
            <tr>
              <th class="px-2 py-2 text-left">Buscar</th>
              <th class="px-2 py-2 text-left">Descripción</th>
              <th class="px-2 py-2 text-left">Clave ProdServ</th>
              <th class="px-2 py-2 text-left">Clave Unidad</th>
              <th class="px-2 py-2 text-left">Unidad</th>
              <th class="px-2 py-2 text-right">Cantidad</th>
              <th class="px-2 py-2 text-right">Precio</th>
              <th class="px-2 py-2 text-right">Desc.</th>
              <th class="px-2 py-2 text-right">Impuestos</th>
              <th class="px-2 py-2 text-right">Importe</th>
              <th class="px-2 py-2"></th>
            </tr>
          </thead>
          <tbody>
            <template x-for="(row, idx) in form.conceptos" :key="row.uid">
              <tr class="border-b border-gray-100 dark:border-gray-700/40 align-top">
                {{-- Buscar (autocompletar) --}}
                <td class="px-2 py-2 w-56 relative">
                  <input type="text" class="form-input w-full" placeholder="Código o texto"
                         x-model.debounce.400ms="row.query" @input="buscarProducto(idx)">
                  <template x-if="row.suggestions && row.suggestions.length">
                    <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 rounded-lg shadow">
                      <ul class="max-h-48 overflow-auto">
                        <template x-for="(s, i) in row.suggestions" :key="i">
                          <li>
                            <button type="button" class="w-full text-left px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    @click="aplicarSugerencia(idx, s)">
                              <div class="text-xs font-medium" x-text="s.descripcion"></div>
                              <div class="text-[10px] text-gray-500" x-text="`ProdServ: ${s.clave_prod_serv} · ClaveUnidad: ${s.clave_unidad}`"></div>
                            </button>
                          </li>
                        </template>
                      </ul>
                    </div>
                  </template>
                </td>

                {{-- Descripción --}}
                <td class="px-2 py-2 w-[28rem]">
                  <input type="text" class="form-input w-full" x-model="row.descripcion" placeholder="Descripción">
                </td>

                {{-- Claves compactas --}}
                <td class="px-2 py-2 w-28">
                  <input type="text" class="form-input w-full text-center" x-model="row.clave_prod_serv" maxlength="8">
                </td>
                <td class="px-2 py-2 w-24">
                  <input type="text" class="form-input w-full text-center" x-model="row.clave_unidad" maxlength="4">
                </td>
                <td class="px-2 py-2 w-24">
                  <input type="text" class="form-input w-full text-center" x-model="row.unidad" maxlength="10">
                </td>

                {{-- Números --}}
                <td class="px-2 py-2 w-24">
                  <input type="number" step="0.001" class="form-input w-full text-right" x-model.number="row.cantidad" @input="recalcularTotales">
                </td>
                <td class="px-2 py-2 w-28">
                  <input type="number" step="0.01" class="form-input w-full text-right" x-model.number="row.precio" @input="recalcularTotales">
                </td>
                <td class="px-2 py-2 w-24">
                  <input type="number" step="0.01" class="form-input w-full text-right" x-model.number="row.descuento" @input="recalcularTotales">
                </td>

                {{-- Impuestos (en esta iteración, botón placeholder) --}}
                <td class="px-2 py-2 w-32 text-right">
                  <button type="button" class="btn-xs bg-violet-500 hover:bg-violet-600 text-white"
                          @click="$dispatch('open-impuestos', { idx })">
                    Editar
                  </button>
                </td>

                {{-- Importe --}}
                <td class="px-2 py-2 w-28 text-right" x-text="money(calcImporte(row))"></td>

                {{-- Quitar --}}
                <td class="px-2 py-2 w-10 text-right">
                  <button type="button" class="text-red-500 hover:text-red-600" @click="eliminarConcepto(idx)">
                    &times;
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      {{-- Totales --}}
      <div class="flex justify-end mt-4">
        <div class="w-full max-w-sm space-y-1 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span x-text="money(totales.subtotal)"></span></div>
          <div class="flex justify-between"><span class="text-gray-500">Descuento</span><span x-text="money(totales.descuento)"></span></div>
          <div class="flex justify-between"><span class="text-gray-500">Impuestos</span><span x-text="money(totales.impuestos)"></span></div>
          <div class="flex justify-between font-semibold text-gray-800 dark:text-gray-100"><span>Total</span><span x-text="money(totales.total)"></span></div>
        </div>
      </div>
    </div>

    {{-- DOCUMENTOS RELACIONADOS --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Documentos relacionados</h2>
        <button type="button" class="btn-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:opacity-90" @click="agregarRelacionado">Agregar</button>
      </div>

      <div class="space-y-2">
        <template x-for="(rel, i) in form.relacionados" :key="rel.uid">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
              <label class="text-xs text-gray-500">Tipo relación</label>
              <input type="text" class="form-input w-full" x-model="rel.tipo_relacion" placeholder="p.ej. 01, 04">
            </div>
            <div class="md:col-span-2">
              <label class="text-xs text-gray-500">UUID</label>
              <input type="text" class="form-input w-full" x-model="rel.uuid" placeholder="UUID a relacionar">
            </div>
            <div class="flex items-end justify-end">
              <button type="button" class="btn-xs text-red-500 hover:text-red-600" @click="eliminarRelacionado(i)">Eliminar</button>
            </div>
          </div>
        </template>
      </div>
    </div>

    {{-- ACCIONES --}}
    <div class="flex items-center justify-end gap-3">
      <button type="button" class="btn border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700" @click="previewFactura">Visualizar</button>
      <button type="button" class="btn bg-gray-900 dark:bg-white dark:text-gray-900 text-white hover:opacity-90" @click="guardarBorrador">Guardar (prefactura)</button>
      <button type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white" @click="timbrarFactura">Timbrar</button>
    </div>

  </div>{{-- /x-data --}}
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
  window.facturaForm = (opts) => ({
    // ----- estado -----
    form: {
      tipo_comprobante: 'I', // default INgreso
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
    clientes: Array.isArray(opts.clientes) ? opts.clientes : [],
    clienteSel: {},
    minFecha: opts.minFecha,
    maxFecha: opts.maxFecha,
    totales: { subtotal: 0, descuento: 0, impuestos: 0, total: 0 },

    // ----- helpers -----
    money(n){ return new Intl.NumberFormat('es-MX',{style:'currency', currency:'MXN'}).format(Number(n||0)); },
    uid(){ return (crypto.randomUUID?.() || String(Date.now()+Math.random())); },

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
      const url = `${opts.apiSeriesNext}?tipo=${encodeURIComponent(this.form.tipo_comprobante)}&rfc=${encodeURIComponent(opts.rfcUsuarioId)}`;
      fetch(url).then(r=>r.json()).then(j=>{
        this.form.serie = j.serie || '';
        this.form.folio = j.folio || '';
      }).catch(()=>{});
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
    },

    // ----- conceptos -----
    agregarConcepto(){
      this.form.conceptos.push({
        uid: this.uid(),
        query: '',
        suggestions: [],
        descripcion: '',
        clave_prod_serv: '',
        clave_unidad: '',
        unidad: '',
        cantidad: 1,
        precio: 0,
        descuento: 0,
        impuestos: [], // pendiente UI impuestos
      });
      this.recalcularTotales();
    },
    eliminarConcepto(i){
      this.form.conceptos.splice(i,1);
      this.recalcularTotales();
    },
    buscarProducto(idx){
      const row = this.form.conceptos[idx];
      if (!row || !row.query || row.query.length < 2) { row.suggestions = []; return; }
      const url = `${opts.apiProductosBuscar}?q=${encodeURIComponent(row.query)}&rfc=${encodeURIComponent(opts.rfcUsuarioId)}`;
      fetch(url).then(r=>r.json()).then(list=>{
        row.suggestions = (list || []).slice(0,8);
      }).catch(()=>{ row.suggestions = []; });
    },
    aplicarSugerencia(idx, s){
      const row = this.form.conceptos[idx];
      if (!row) return;
      row.descripcion     = s.descripcion ?? row.descripcion;
      row.clave_prod_serv = s.clave_prod_serv ?? row.clave_prod_serv;
      row.clave_unidad    = s.clave_unidad ?? row.clave_unidad;
      row.unidad          = s.unidad ?? row.unidad;
      if (typeof s.precio !== 'undefined') row.precio = Number(s.precio);
      row.query = '';
      row.suggestions = [];
      this.recalcularTotales();
    },
    calcImporte(row){
      const subt = Number(row.cantidad||0) * Number(row.precio||0);
      const desc = Number(row.descuento||0);
      return Math.max(0, subt - desc);
    },
    recalcularTotales(){
      let subtotal=0, descuento=0, impuestos=0;
      for (const r of this.form.conceptos){
        const sub = Number(r.cantidad||0) * Number(r.precio||0);
        const des = Number(r.descuento||0);
        subtotal += sub;
        descuento += des;
        // impuestos por ahora 0; cuando agreguemos IU de impuestos por concepto, sumar aquí
      }
      const total = subtotal - descuento + impuestos;
      this.totales = { subtotal, descuento, impuestos, total };
    },

    // ----- relacionados -----
    agregarRelacionado(){
      this.form.relacionados.push({ uid:this.uid(), tipo_relacion:'', uuid:'' });
    },
    eliminarRelacionado(i){
      this.form.relacionados.splice(i,1);
    },

    // ----- acciones -----
    previewFactura(){
      // TODO: post a /facturacion/facturas/preview con this.form
      alert('Previsualización: pendiente de integrar endpoint /preview');
    },
    guardarBorrador(){
      // TODO: post a /facturacion/facturas/guardar con this.form (estatus prefactura)
      alert('Guardar prefactura: pendiente de integrar endpoint /guardar');
    },
    timbrarFactura(){
      // TODO validaciones -> post al endpoint de timbrado
      alert('Timbrar: pendiente de integrar lógica de timbrado');
    },
  });
});
</script>
@endpush
