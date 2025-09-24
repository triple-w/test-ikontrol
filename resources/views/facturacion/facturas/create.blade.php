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

  {{-- ... layout/header igual ... --}}

<div
  x-data='facturaForm({
    rfcUsuarioId: {{ (int) $rfcUsuarioId }},
    clientes: {!! json_encode($clientes, JSON_UNESCAPED_UNICODE) !!},
    minFecha: "{{ $minFecha }}",
    maxFecha: "{{ $maxFecha }}",
    apiSeriesNext: "{{ route('api.series.next') }}",
    apiProductosBuscar: "{{ route('api.productos.buscar') }}",
    apiSatProdServ: "{{ route('api.sat.clave_prod_serv') }}",
    apiSatUnidad: "{{ route('api.sat.clave_unidad') }}",
    routeClienteUpdateBase: "{{ url('/catalogos/clientes') }}",
    routePreview: "{{ route('facturas.preview') }}",
    csrf: "{{ csrf_token() }}",
    initial: {!! isset($borrador) ? json_encode($borrador->payload, JSON_UNESCAPED_UNICODE) : 'null' !!},
    borradorId: {!! isset($borrador) ? (int) $borrador->id : 'null' !!}
  })'
  x-init="init()"
  class="...">

  {{-- FORM oculto PREVIEW --}}
  <form x-ref="previewForm" action="{{ route('facturas.preview') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="payload" :value="JSON.stringify(form)">
  </form>

  {{-- FORM oculto GUARDAR --}}
  <form x-ref="guardarForm" action="{{ route('facturas.guardar') }}" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="payload" :value="JSON.stringify(form)">
  </form>

  {{-- ... resto de tu vista tal cual ... --}}

  {{-- MODAL LATERAL: EDITAR CLIENTE --}}
  <div x-data="{open:false}" x-ref="drawerCliente"
       x-on:open-modal.window="if($event.detail==='modalEditarCliente') open=true"
       x-show="open" x-transition.opacity
       class="fixed inset-0 z-40" style="display:none">
    {{-- backdrop + panel derecho igual --}}
    {{-- ... tus inputs que llenan this.clienteEdit ... --}}
    <button type="button" @click="submitEditarCliente()" class="btn ...">Guardar</button>
  </div>
</div>

<script>
window.facturaForm = (opts) => ({
  opts,
  form: {
    borrador_id: null,
    tipo_comprobante: 'I',
    serie: '', folio: '',
    fecha: new Date().toISOString().slice(0,16),
    metodo_pago: 'PUE',
    forma_pago: '99',
    cliente_id: null,
    comentarios_pdf: '',
    relacionados: [],
    conceptos: [],
  },
  clientes: opts.clientes || [],
  clienteEdit: {},

  init(){
    if (opts.initial) {
      this.form = Object.assign(this.form, opts.initial || {});
      if (opts.borradorId) this.form.borrador_id = opts.borradorId;
      this.onClienteChange();
      this.recalcularTotales?.();
    }
  },

  onClienteChange(){ /* tu lógica existente de pintar resumen cliente */ },

  pedirSiguienteFolio(){
    const url = `${opts.apiSeriesNext}?tipo=${encodeURIComponent(this.form.tipo_comprobante)}`;
    fetch(url).then(r=>r.json()).then(j=>{
      this.form.serie = j.serie || '';
      this.form.folio = (j.folio != null ? j.folio : j.siguiente) || '';
    }).catch(()=>{});
  },

  previsualizar(){
    if (!this.form.cliente_id) { alert('Selecciona un cliente'); return; }
    if (!this.form.serie || !this.form.folio) { alert('Serie/Folio inválidos'); return; }
    if (!this.form.conceptos.length) { alert('Agrega al menos un concepto'); return; }
    this.$refs.previewForm.submit();
  },

  guardarBorrador(){
    if (!this.form.cliente_id) { alert('Selecciona un cliente'); return; }
    if (!this.form.serie || !this.form.folio) { alert('Serie/Folio inválidos'); return; }
    if (!this.form.conceptos.length) { alert('Agrega al menos un concepto'); return; }
    this.$refs.guardarForm.submit();
  },

  async submitEditarCliente(){
    if (!this.form.cliente_id) return;
    const url = `${this.opts.routeClienteUpdateBase}/${this.form.cliente_id}/quick-update`;
    const body = new URLSearchParams();
    body.append('_token', this.opts.csrf);
    body.append('_method','PUT');
    for (const [k,v] of Object.entries(this.clienteEdit)) body.append(k, v ?? '');

    const r = await fetch(url, {
      method:'POST',
      headers:{ 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' },
      body
    });
    if (!r.ok) { alert('No se pudo actualizar el cliente'); return; }

    const j = await r.json().catch(()=>null);
    if (j && j.id){
      const i = this.clientes.findIndex(x => Number(x.id)===Number(j.id));
      if (i>=0) this.clientes.splice(i,1,j);
      this.onClienteChange();
    }
    const drawer = this.$refs.drawerCliente;
    if (drawer && drawer.__x) drawer.__x.$data.open = false;
  },
});
</script>


@push('scripts')
<script>
  window.facturaForm = (opts) => ({
      init(){
      if (opts?.initial) {
        // conserva defaults pero sobreescribe con lo del borrador
        this.form = Object.assign(this.form, opts.initial || {});
        if (opts?.borradorId) this.form.borrador_id = opts.borradorId;
        this.onClienteChange();
        this.recalcularTotales();
      }
    },

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
<<<<<<< HEAD

      // POST + _method=PUT a /catalogos/clientes/{id}/quick-update
      const url = `${this.opts.routeClienteUpdateBase}/${this.form.cliente_id}/quick-update`;
=======
      const url = `${opts.routeClienteUpdateBase}/${this.form.cliente_id}`;
>>>>>>> parent of c14a727 (cambios)
      const body = new URLSearchParams();
      body.append('_token', opts.csrf);
      body.append('_method','PUT');
      for (const [k,v] of Object.entries(this.clienteEdit)) body.append(k, v ?? '');
      const r = await fetch(url, { method:'POST', headers:{'Accept':'application/json'}, body });
      if (!r.ok) { alert('No se pudo actualizar el cliente'); return; }
      const j = await r.json().catch(()=>null);
      if (j && j.id){
<<<<<<< HEAD
        const i = this.clientes.findIndex(x => Number(x.id)===Number(j.id));
        if (i>=0) this.clientes.splice(i,1,j);
        this.onClienteChange(); // refresca el panel de cliente
      }

      // cierra el drawer correctamente
      const drawer = this.$refs.drawerCliente;
      if (drawer && drawer.__x) drawer.__x.$data.open = false;
    },



=======
        // actualiza en la lista local
        const i = this.clientes.findIndex(x => Number(x.id)===Number(j.id));
        if (i>=0) this.clientes.splice(i,1,j);
        this.onClienteChange();
      } else {
        // si el controlador respondió redirect/html, al menos refrescamos la UI local
        this.onClienteChange();
      }
      // cierra drawer
      //document.querySelector('[x-ref=drawerCliente]')?.classList.add('hidden');
      // cerrar drawer correctamente
      const drawer = document.querySelector('[x-ref=drawerCliente]');
      if (drawer && drawer.__x) drawer.__x.$data.open = false;
    },

>>>>>>> parent of c14a727 (cambios)
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
    if (!this.form.cliente_id) { alert('Selecciona un cliente'); return; }
    if (!this.form.serie || !this.form.folio) { alert('Serie/Folio inválidos'); return; }
    if (!this.form.conceptos.length) { alert('Agrega al menos un concepto'); return; }
    this.$refs.guardarForm.submit();
    },
  });

</script>
@endpush
