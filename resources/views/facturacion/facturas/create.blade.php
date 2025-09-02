@extends('layouts.app')

@section('content')
<div
    x-data="facturaForm({
        clientes: @js($clientes),
        seedProds: @js($seedProds),
        fechaMin: '{{ $fechaMin }}',
        fechaMax: '{{ $fechaMax }}',
        emisor: @js([
            'nombre' => $emisor->razon_social ?? '',
            'rfc'    => $emisor->rfc ?? '',
            'cp'     => $emisor->codigo_postal ?? '',
        ]),
    })"
    class="space-y-6"
>
    {{-- Header --}}
    <div class="flex items-start justify-between">
        <h1 class="text-2xl font-semibold">Nueva Factura</h1>
        <div class="text-right">
            <div class="text-xs uppercase text-gray-400">Emisor</div>
            <div class="text-sm font-medium text-gray-900">
                <span x-text="emisor.nombre"></span>
            </div>
            <div class="text-xs text-gray-500">
                RFC: <span x-text="emisor.rfc"></span> · CP: <span x-text="emisor.cp"></span>
            </div>
        </div>
    </div>

    {{-- Datos CFDI / horizontales --}}
    <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-5">
        <div class="grid gap-4 md:grid-cols-3 lg:grid-cols-4">
            <div>
                <label class="label">Tipo de comprobante</label>
                <select x-model="encabezado.tipo"
                        @change="onTipoChange()"
                        class="input">
                    <option value="I">Ingreso</option>
                    <option value="E">Egreso (Nota de crédito)</option>
                    <option value="P">Pago (Complemento)</option>
                    <option value="N">Nómina</option>
                </select>
            </div>

            <div>
                <label class="label">Fecha y hora</label>
                <input type="datetime-local" class="input"
                       x-model="encabezado.fecha"
                       :min="fechaMin" :max="fechaMax">
                <p class="text-[11px] text-gray-500 mt-1">Solo dentro de las últimas 72 h.</p>
            </div>

            <div>
                <label class="label">Serie</label>
                <input type="text" class="input" x-model="encabezado.serie" readonly>
            </div>
            <div>
                <label class="label">Folio</label>
                <input type="number" class="input" x-model.number="encabezado.folio" readonly>
            </div>

            <div class="md:col-span-2 lg:col-span-4">
                <label class="label">Comentarios</label>
                <textarea class="input h-20" x-model="encabezado.comentarios" placeholder="Opcional"></textarea>
            </div>
        </div>
    </section>

    {{-- Cliente (labels + modal editar) --}}
    <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-5">
        <div class="grid gap-4 lg:grid-cols-4 items-end">
            <div class="lg:col-span-2">
                <label class="label">Cliente</label>
                <select class="input" x-model="cliente.id" @change="onClienteChange()">
                    <option value="">Selecciona…</option>
                    <template x-for="c in clientes" :key="c.id">
                        <option :value="c.id" x-text="c.razon_social + ' ('+c.rfc+')'"></option>
                    </template>
                </select>
            </div>
            <div>
                <button type="button" class="btn" @click="abrirModalCliente()">Actualizar cliente</button>
            </div>
        </div>

        {{-- 👇 Etiquetas con TUS campos (sin cajas extra) --}}
        <div class="mt-4 grid gap-2 md:grid-cols-3 lg:grid-cols-6">
            <div class="label-block"><span>Razón social</span><strong x-text="cliente.razon_social || '—'"></strong></div>
            <div class="label-block"><span>RFC</span><strong x-text="cliente.rfc || '—'"></strong></div>
            <div class="label-block"><span>Calle</span><strong x-text="cliente.calle || '—'"></strong></div>
            <div class="label-block"><span>No. ext</span><strong x-text="cliente.no_ext || '—'"></strong></div>
            <div class="label-block"><span>No. int</span><strong x-text="cliente.no_int || '—'"></strong></div>
            <div class="label-block"><span>Colonia</span><strong x-text="cliente.colonia || '—'"></strong></div>
            <div class="label-block"><span>Localidad</span><strong x-text="cliente.localidad || '—'"></strong></div>
            <div class="label-block"><span>Estado</span><strong x-text="cliente.estado || '—'"></strong></div>
            <div class="label-block"><span>Código postal</span><strong x-text="cliente.codigo_postal || '—'"></strong></div>
            <div class="label-block"><span>País</span><strong x-text="cliente.pais || '—'"></strong></div>
            <div class="label-block"><span>Email</span><strong x-text="cliente.email || '—'"></strong></div>
        </div>
    </section>


    {{-- Conceptos --}}
    <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-5" x-id="['row']">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-medium">Conceptos</h3>
            <div class="space-x-2">
                <button class="btn-secondary" @click="agregarConcepto()">+ Agregar</button>
                <button class="btn-secondary" @click="vaciarConceptos()">Vaciar</button>
            </div>
        </div>

        <template x-for="(row, idx) in conceptos" :key="row.key">
            <div class="rounded-lg ring-1 ring-gray-200 p-3 mb-3">
                {{-- búsqueda (autocompletar) --}}
                <div class="grid gap-3 lg:grid-cols-12 items-end">
                    <div class="lg:col-span-4">
                        <label class="label">Buscar producto</label>
                        <input class="input" type="text" x-model="row.search"
                               @input.debounce.300ms="buscarProducto(idx)"
                               placeholder="Escribe para buscar…">
                        <div class="mt-1 bg-white border rounded-md max-h-40 overflow-auto"
                             x-show="row.sugerencias.length">
                            <template x-for="s in row.sugerencias" :key="s.id">
                                <button type="button" class="px-3 py-1.5 text-left w-full hover:bg-gray-50"
                                        @click="seleccionarProducto(idx, s)">
                                    <span class="font-medium" x-text="s.descripcion"></span>
                                    <span class="text-xs text-gray-500 ml-2" x-text="'$'+Number(s.precio).toFixed(2)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="label">Descripción</label>
                        <input class="input" x-model="row.descripcion">
                    </div>

                    <div>
                        <label class="label">Clave ProdServ</label>
                        <input class="input w-28" x-model="row.clave_prod_serv_id">
                    </div>
                    <div>
                        <label class="label">Clave Unidad</label>
                        <input class="input w-24" x-model="row.clave_unidad_id">
                    </div>
                    <div>
                        <label class="label">Unidad</label>
                        <input class="input w-24" x-model="row.unidad">
                    </div>

                    <div>
                        <label class="label">Cantidad</label>
                        <input class="input w-24" type="number" min="0" step="0.001" x-model.number="row.cantidad" @input="recalcular(idx)">
                    </div>
                    <div>
                        <label class="label">Precio</label>
                        <input class="input w-28" type="number" min="0" step="0.01" x-model.number="row.precio" @input="recalcular(idx)">
                    </div>
                    <div>
                        <label class="label">Descuento</label>
                        <input class="input w-28" type="number" min="0" step="0.01" x-model.number="row.descuento" @input="recalcular(idx)">
                    </div>

                    <div class="lg:col-span-12 flex flex-wrap items-center gap-2">
                        <span class="text-sm text-gray-500">Impuestos:</span>
                        <button type="button" class="badge" @click="agregarImpuesto(idx,'trasladado')">+ Trasladado</button>
                        <button type="button" class="badge" @click="agregarImpuesto(idx,'retenido')">+ Retenido</button>
                    </div>

                    {{-- lista de impuestos --}}
                    <div class="lg:col-span-12 grid gap-2 md:grid-cols-2">
                        <template x-for="(imp, i) in row.trasladados" :key="'t'+i">
                            <div class="flex items-end gap-2">
                                <select class="input w-28" x-model="imp.impuesto">
                                    <option value="IVA">IVA</option><option value="IEPS">IEPS</option><option value="ISR">ISR</option>
                                </select>
                                <select class="input w-28" x-model="imp.factor">
                                    <option value="Tasa">Tasa</option><option value="Cuota">Cuota</option><option value="Exento">Exento</option>
                                </select>
                                <input class="input w-28" type="number" step="0.000001" placeholder="Tasa/Valor" x-model.number="imp.tasa" @input="recalcular(idx)">
                                <button class="btn-danger" @click="row.trasladados.splice(i,1)">Quitar</button>
                                <span class="ml-auto text-sm">Importe: <strong x-text="fmt(imp.importe)"></strong></span>
                            </div>
                        </template>

                        <template x-for="(imp, i) in row.retenidos" :key="'r'+i">
                            <div class="flex items-end gap-2">
                                <select class="input w-28" x-model="imp.impuesto">
                                    <option value="IVA">IVA</option><option value="ISR">ISR</option>
                                </select>
                                <select class="input w-28" x-model="imp.factor">
                                    <option value="Tasa">Tasa</option><option value="Cuota">Cuota</option>
                                </select>
                                <input class="input w-28" type="number" step="0.000001" placeholder="Tasa/Valor" x-model.number="imp.tasa" @input="recalcular(idx)">
                                <button class="btn-danger" @click="row.retenidos.splice(i,1)">Quitar</button>
                                <span class="ml-auto text-sm">Importe: <strong x-text="fmt(imp.importe)"></strong></span>
                            </div>
                        </template>
                    </div>

                    <div class="lg:col-span-12 flex items-center justify-between">
                        <div class="text-sm text-gray-600">Subtotal: <strong x-text="fmt(row.subtotal)"></strong> · Total concepto: <strong x-text="fmt(row.total)"></strong></div>
                        <button class="btn-danger" @click="eliminarConcepto(idx)">Eliminar concepto</button>
                    </div>
                </div>
            </div>
        </template>
    </section>

    {{-- Documentos relacionados --}}
    <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-medium">Documentos relacionados</h3>
            <button class="btn-secondary" @click="agregarRelacion()">+ Agregar</button>
        </div>

        <template x-for="(r, i) in relaciones" :key="r.key">
            <div class="grid gap-3 md:grid-cols-5 items-end mb-3">
                <div>
                    <label class="label">Tipo relación</label>
                    <select class="input" x-model="r.tipo">
                        <option value="01">01 - Nota de crédito</option>
                        <option value="02">02 - Nota de débito</option>
                        <option value="03">03 - Devolución</option>
                        <option value="04">04 - Sustitución</option>
                        <option value="07">07 - CFDI por aplicación de anticipo</option>
                        <!-- agrega los que necesites -->
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="label">UUID</label>
                    <input class="input" x-model="r.uuid" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                </div>
                <div>
                    <button class="btn-danger" @click="relaciones.splice(i,1)">Quitar</button>
                </div>
            </div>
        </template>
    </section>

    {{-- Totales + Acciones --}}
    <section class="bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 p-5">
        <div class="flex items-center justify-between">
            <div class="space-y-1 text-sm">
                <div>Subtotal: <strong x-text="fmt(totales.subtotal)"></strong></div>
                <div>Impuestos trasl.: <strong x-text="fmt(totales.trasladados)"></strong></div>
                <div>Impuestos ret.: <strong x-text="fmt(totales.retenidos)"></strong></div>
                <div class="text-lg">Total: <strong x-text="fmt(totales.total)"></strong></div>
            </div>
            <div class="space-x-2">
                <button class="btn-secondary" @click="vistaPrevia()">Vista previa</button>
                <button class="btn" @click="guardar('borrador')">Guardar (prefactura)</button>
                <button class="btn-primary" @click="guardar('timbrar')">Timbrar ahora</button>
            </div>
        </div>
    </section>

    {{-- Modal cliente (placeholder) --}}
    <div x-show="modalCliente" class="fixed inset-0 bg-black/40 grid place-items-center p-4" x-transition>
        <div class="bg-white rounded-xl w-full max-w-2xl p-5">
            <div class="flex justify-between mb-3">
                <h3 class="font-semibold">Actualizar cliente</h3>
                <button @click="modalCliente=false">✕</button>
            </div>
            <p class="text-sm text-gray-600">Integra aquí tu formulario de edición de cliente.</p>
        </div>
    </div>

    {{-- Modal vista previa --}}
    <div x-show="modalPreview" class="fixed inset-0 bg-black/40 grid place-items-center p-4" x-transition>
        <div class="bg-white rounded-xl w-full max-w-5xl h-[80vh] p-0 overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3 border-b">
                <h3 class="font-semibold">Vista previa</h3>
                <button @click="modalPreview=false">✕</button>
            </div>
            <iframe class="w-full h-full" :srcdoc="previewHtml"></iframe>
        </div>
    </div>
</div>

{{-- estilos utilitarios --}}
<style>
    .label{ @apply block text-sm text-gray-600 mb-1; }
    .input{ @apply w-full rounded-lg border-gray-300 focus:border-violet-500 focus:ring-violet-500; }
    .btn{ @apply inline-flex items-center rounded-lg bg-violet-600 text-white px-3 py-2 text-sm hover:bg-violet-700; }
    .btn-primary{ @apply inline-flex items-center rounded-lg bg-emerald-600 text-white px-3 py-2 text-sm hover:bg-emerald-700;}
    .btn-secondary{ @apply inline-flex items-center rounded-lg bg-gray-100 text-gray-900 px-3 py-2 text-sm hover:bg-gray-200; }
    .btn-danger{ @apply inline-flex items-center rounded-lg bg-rose-500 text-white px-3 py-2 text-sm hover:bg-rose-600; }
    .badge{ @apply inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs hover:bg-gray-200; }
    .label-block{ @apply text-xs; } .label-block span{ @apply block text-gray-500; } .label-block strong{ @apply text-gray-900; }
</style>

{{-- lógica Alpine --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('facturaForm', (cfg) => ({
        // props
        clientes: cfg.clientes || [],
        seedProds: cfg.seedProds || [],
        fechaMin: cfg.fechaMin, fechaMax: cfg.fechaMax,
        emisor: cfg.emisor,

        // estado
        encabezado: {
            tipo: 'I', fecha: new Date().toISOString().slice(0,16),
            serie: '', folio: '', comentarios: ''
        },
        cliente: {},
        conceptos: [],
        relaciones: [],
        totales: { subtotal:0, trasladados:0, retenidos:0, total:0 },

        modalCliente: false,
        modalPreview: false,
        previewHtml: '<div style="padding:24px;font-family:ui-sans-serif">Cargando…</div>',

        init() {
            // set fecha límites
            const now = new Date();
            const max = cfg.fechaMax; const min = cfg.fechaMin;
            if (this.encabezado.fecha < min) this.encabezado.fecha = min;
            if (this.encabezado.fecha > max) this.encabezado.fecha = max;

            this.onTipoChange();
            this.agregarConcepto(); // al menos uno
        },

        onTipoChange() {
            fetch(`{{ route('api.series.next') }}?tipo=${this.encabezado.tipo}`)
                .then(r => r.json())
                .then(j => { this.encabezado.serie = j.serie; this.encabezado.folio = j.folio; });
        },

        onClienteChange() {
            const c = this.clientes.find(x => String(x.id) === String(this.cliente.id));
            this.cliente = c || {};
        },

        abrirModalCliente(){ this.modalCliente = true; },

        agregarConcepto(){
            this.conceptos.push({
                key: crypto.randomUUID(),
                search: '', sugerencias: [],
                descripcion: '', clave_prod_serv_id:'', clave_unidad_id:'', unidad:'',
                cantidad: 1, precio: 0, descuento: 0,
                trasladados: [], retenidos: [],
                subtotal: 0, total: 0,
            });
        },
        eliminarConcepto(i){ this.conceptos.splice(i,1); this.recalcularTodo(); },
        vaciarConceptos(){ this.conceptos = []; this.agregarConcepto(); },

        buscarProducto(i){
            const row = this.conceptos[i];
            const q = row.search.trim();
            if (q.length < 2) { row.sugerencias = []; return; }
            fetch(`{{ route('api.productos.buscar') }}?q=`+encodeURIComponent(q))
                .then(r => r.json())
                .then(list => { row.sugerencias = list; });
        },
        seleccionarProducto(i, p){
            const row = this.conceptos[i];
            row.sugerencias = []; row.search = p.descripcion;
            row.descripcion = p.descripcion;
            row.precio = Number(p.precio || 0);
            row.clave_prod_serv_id = p.clave_prod_serv_id || '';
            row.clave_unidad_id = p.clave_unidad_id || '';
            row.unidad = p.unidad || '';
            this.recalcular(i);
        },

        agregarImpuesto(i, tipo){
            const base = { impuesto:'IVA', factor:'Tasa', tasa:0, importe:0 };
            this.conceptos[i][ tipo === 'trasladado' ? 'trasladados':'retenidos' ].push({...base});
        },

        recalcular(i){
            const r = this.conceptos[i];
            const base = Math.max(0, (Number(r.cantidad)||0) * (Number(r.precio)||0) - (Number(r.descuento)||0));
            r.subtotal = base;

            // calcular impuestos
            const calcImp = (arr, signo=1) => {
                let sum=0;
                arr.forEach(imp => {
                    const tasa = Number(imp.tasa)||0;
                    imp.importe = imp.factor === 'Exento' ? 0 :
                        (imp.factor === 'Tasa' ? base * tasa : tasa) * signo;
                    sum += imp.importe;
                });
                return sum;
            };
            const tTras = calcImp(r.trasladados, +1);
            const tRet  = calcImp(r.retenidos,  -1);
            r.total = base + tTras + tRet;

            this.recalcularTodo();
        },
        recalcularTodo(){
            const tot = { subtotal:0, trasladados:0, retenidos:0, total:0 };
            this.conceptos.forEach(r => {
                tot.subtotal += r.subtotal;
                tot.trasladados += r.trasladados.reduce((a,b)=>a+(b.importe||0),0);
                tot.retenidos  += r.retenidos.reduce((a,b)=>a+(b.importe||0),0);
                tot.total      += r.total;
            });
            this.totales = tot;
        },

        agregarRelacion(){
            this.relaciones.push({ key: crypto.randomUUID(), tipo:'01', uuid:'' });
        },

        vistaPrevia(){
            const payload = this.payload();
            fetch(`{{ route('facturas.preview') }}`, {
                method:'POST', headers:{'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify(payload)
            }).then(r => r.text())
              .then(html => { this.previewHtml = html; this.modalPreview = true; });
        },

        guardar(accion){
            const payload = this.payload(); payload.accion = accion;
            fetch(`{{ route('facturas.store') }}`, {
                method:'POST', headers:{'Content-Type':'application/json', 'X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify(payload)
            }).then(() => window.location.reload());
        },

        payload(){
            return {
                encabezado: this.encabezado,
                cliente: this.cliente,
                conceptos: this.conceptos,
                relaciones: this.relaciones,
                totales: this.totales,
            };
        },

        fmt(n){ return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(Number(n||0)); },
    }))
})
</script>
@endsection
