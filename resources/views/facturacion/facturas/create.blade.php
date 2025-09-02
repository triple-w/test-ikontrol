@extends('layouts.app')

@section('title', 'Nueva Factura')

@push('styles')
    {{-- estilos propios de esta pantalla (si los necesitas) --}}
@endpush

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

    {{-- Encabezado + RFC activo a la derecha --}}
    <div class="flex items-start justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>

        {{-- Etiqueta RFC activo (se alimenta desde el middleware que comparte la variable) --}}
        <div class="flex items-center gap-2">
            <span class="text-xs uppercase text-gray-400 dark:text-gray-500">RFC activo</span>
            <div class="px-2 py-1 rounded bg-violet-500/10 text-violet-600 dark:text-violet-400 text-sm">
                {{ $rfcActivo ?? '—' }}
            </div>
        </div>
    </div>

    @php
        // Fallbacks por si algo no llega del controlador
        $rfcUsuarioId = $rfcUsuarioId ?? (session('rfc_usuario_id') ?? session('rfc_activo_id') ?? 0);

        $clientesJson = ($clientes ?? collect())->map(function ($c) {
            return [
                'id'             => $c->id,
                'rfc'            => $c->rfc,
                'razon_social'   => $c->razon_social,
                'calle'          => $c->calle,
                'no_ext'         => $c->no_ext,
                'no_int'         => $c->no_int,
                'colonia'        => $c->colonia,
                'localidad'      => $c->localidad,
                'estado'         => $c->estado,
                'codigo_postal'  => $c->codigo_postal,
                'pais'           => $c->pais,
                'email'          => $c->email,
            ];
        })->values()->toJson();

        // Ventana de 72h para fecha de CFDI
        $minFecha = $minFecha ?? now()->copy()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha = $maxFecha ?? now()->format('Y-m-d\TH:i');
    @endphp

    {{-- Contenedor Alpine con tu store de la factura (NO incluimos plugins aquí) --}}
    <div
        x-data="facturaForm({
            rfcUsuarioId: {{ (int) $rfcUsuarioId }},
            clientes: {!! $clientesJson !!},
            minFecha: '{{ $minFecha }}',
            maxFecha: '{{ $maxFecha }}',
            apiSeriesNext: '{{ url('/api/series/next') }}',
            apiProductosBuscar: '{{ url('/api/productos/buscar') }}',
            routeClienteUpdateBase: '{{ url('/catalogos/clientes') }}', // PUT /{id}
            csrf: '{{ csrf_token() }}'
        })"
        class="space-y-8"
    >

        {{-- Sección: Datos del comprobante --}}
        <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Datos del comprobante</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Tipo de comprobante --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo de comprobante</label>
                    <select x-model="form.tipo_comprobante" @change="onTipoComprobanteChange"
                            class="form-select w-full">
                        <option value="I">Ingreso (Factura, Honorarios, Arrendamiento)</option>
                        <option value="E">Egreso (Nota de Crédito)</option>
                        <option value="T">Traslado</option>
                        <option value="N">Nómina</option>
                        <option value="P">Pago</option>
                    </select>
                </div>

                {{-- Serie (auto por tipo) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Serie</label>
                    <input type="text" x-model="form.serie" class="form-input w-full" placeholder="Se autollenará" readonly>
                </div>

                {{-- Folio (siguiente) --}}
                <div>
                    <div class="flex items-center justify-between">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Folio</label>
                        <button type="button" class="text-xs text-violet-600 hover:text-violet-700"
                                @click="pedirSiguienteFolio">Actualizar folio</button>
                    </div>
                    <input type="text" x-model="form.folio" class="form-input w-full" placeholder="Siguiente folio" readonly>
                </div>

                {{-- Fecha (límite 72h) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha y hora</label>
                    <input type="datetime-local" class="form-input w-full"
                           :min="minFecha" :max="maxFecha" x-model="form.fecha">
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

                {{-- Forma de pago --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Forma de pago</label>
                    <input type="text" x-model="form.forma_pago" class="form-input w-full" placeholder="Ej. 03">
                </div>

                {{-- Comentarios (textarea) --}}
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comentarios en PDF</label>
                    <textarea x-model="form.comentarios_pdf" rows="3" class="form-textarea w-full" placeholder="Notas que aparecerán en el PDF"></textarea>
                </div>
            </div>
        </div>

        {{-- Sección: Cliente (labels planos, botón Actualizar -> abre modal) --}}
        <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5" x-data>
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
                    <button type="button" class="btn-sm bg-violet-500 hover:bg-violet-600 text-white"
                            :disabled="!form.cliente_id"
                            @click="$dispatch('open-modal','modalEditarCliente')">Actualizar</button>
                </div>
            </div>

            {{-- Labels “limpios” (sin card dentro de card) --}}
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

        {{-- Sección: Conceptos (en horizontal “tipo template”) --}}
        <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5" x-data>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Conceptos</h2>
                <button type="button" class="btn-sm bg-gray-900 dark:bg-white dark:text-gray-900 text-white hover:opacity-90"
                        @click="agregarConcepto()">Agregar concepto</button>
            </div>

            {{-- Tabla compacta al estilo template --}}
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
                            <tr class="border-b border-gray-100 dark:border-gray-700/40">
                                {{-- Buscar producto (autocompletar) --}}
                                <td class="px-2 py-2 w-52">
                                    <input type="text" class="form-input w-full" placeholder="Código o texto"
                                           x-model.debounce.400ms="row.query"
                                           @input="buscarProducto(idx)">
                                </td>

                                {{-- Descripción --}}
                                <td class="px-2 py-2 w-[28rem]">
                                    <input type="text" class="form-input w-full" x-model="row.descripcion" placeholder="Descripción">
                                </td>

                                {{-- Claves compactas (anchos chicos) --}}
                                <td class="px-2 py-2 w-28">
                                    <input type="text" class="form-input w-full text-center" x-model="row.clave_prod_serv" maxlength="8">
                                </td>
                                <td class="px-2 py-2 w-24">
                                    <input type="text" class="form-input w-full text-center" x-model="row.clave_unidad" maxlength="4">
                                </td>
                                <td class="px-2 py-2 w-24">
                                    <input type="text" class="form-input w-full text-center" x-model="row.unidad" maxlength="10">
                                </td>

                                {{-- Números a la derecha, cajas compactas --}}
                                <td class="px-2 py-2 w-24">
                                    <input type="number" step="0.001" class="form-input w-full text-right" x-model.number="row.cantidad">
                                </td>
                                <td class="px-2 py-2 w-28">
                                    <input type="number" step="0.01" class="form-input w-full text-right" x-model.number="row.precio">
                                </td>
                                <td class="px-2 py-2 w-24">
                                    <input type="number" step="0.01" class="form-input w-full text-right" x-model.number="row.descuento">
                                </td>

                                {{-- Impuestos (abre modal impuestos por concepto) --}}
                                <td class="px-2 py-2 w-32 text-right">
                                    <button type="button" class="btn-xs bg-violet-500 hover:bg-violet-600 text-white"
                                            @click="$dispatch('open-impuestos', { idx })">
                                        Editar
                                    </button>
                                </td>

                                {{-- Importe --}}
                                <td class="px-2 py-2 w-28 text-right" x-text="money(row.importe)"></td>

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

        {{-- Sección: Relación de documentos (n >= 0) --}}
        <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Documentos relacionados</h2>
                <button type="button" class="btn-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:opacity-90"
                        @click="agregarRelacionado()">Agregar</button>
            </div>

            <div class="space-y-2">
                <template x-for="(rel, i) in form.relacionados" :key="rel.uid">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <label class="text-xs text-gray-500">Tipo relación</label>
                            <input type="text" class="form-input w-full" x-model="rel.tipo_relacion" placeholder="p. ej. 01, 04">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-gray-500">UUID</label>
                            <input type="text" class="form-input w-full" x-model="rel.uuid" placeholder="UUID a relacionar">
                        </div>
                        <div class="flex items-end justify-end">
                            <button type="button" class="btn-xs text-red-500 hover:text-red-600" @click="eliminarRelacionado(i)">
                                Eliminar
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-end gap-3">
            <button type="button" class="btn border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                    @click="previewFactura">Visualizar</button>

            <button type="button" class="btn bg-gray-900 dark:bg-white dark:text-gray-900 text-white hover:opacity-90"
                    @click="guardarBorrador">Guardar (prefactura)</button>

            <button type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white"
                    @click="timbrarFactura">Timbrar</button>
        </div>

    </div> {{-- /x-data --}}
</div>
@endsection

@push('scripts')
    {{-- Scripts específicos de esta pantalla (solo lógica de la pantalla, no plugins globales) --}}
    <script>
        document.addEventListener('alpine:init', () => {
            // registra tu store/funciones facturaForm SIN volver a registrar Alpine persist, etc.
            window.facturaForm = (opts) => ({
                // ... tu estado, métodos y computados existentes ...
                form: {
                    tipo_comprobante: 'I',
                    serie: '',
                    folio: '',
                    fecha: opts.maxFecha,
                    metodo_pago: 'PUE',
                    forma_pago: '03',
                    comentarios_pdf: '',
                    cliente_id: '',
                    conceptos: [],
                    relacionados: [],
                },
                clientes: opts.clientes || [],
                clienteSel: {},
                totales: { subtotal: 0, descuento: 0, impuestos: 0, total: 0 },

                minFecha: opts.minFecha,
                maxFecha: opts.maxFecha,

                money(n) { return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(n || 0)); },

                onTipoComprobanteChange() { this.pedirSiguienteFolio(); },
                pedirSiguienteFolio() {
                    fetch(`${opts.apiSeriesNext}?tipo=${this.form.tipo_comprobante}&rfc=${opts.rfcUsuarioId}`)
                        .then(r => r.json()).then(j => { this.form.serie = j.serie || ''; this.form.folio = j.folio || ''; });
                },

                onClienteChange() {
                    const c = this.clientes.find(x => Number(x.id) === Number(this.form.cliente_id));
                    this.clienteSel = c || {};
                },

                agregarConcepto() {
                    this.form.conceptos.push({
                        uid: crypto.randomUUID?.() || String(Date.now() + Math.random()),
                        query: '',
                        descripcion: '',
                        clave_prod_serv: '',
                        clave_unidad: '',
                        unidad: '',
                        cantidad: 1,
                        precio: 0,
                        descuento: 0,
                        impuestos: [], // aquí abrirás modal para editar
                        get importe() { return (this.cantidad * this.precio) - (this.descuento || 0); },
                    });
                },
                eliminarConcepto(i) { this.form.conceptos.splice(i, 1); },

                buscarProducto(idx) {
                    const row = this.form.conceptos[idx];
                    if (!row || !row.query || row.query.length < 2) return;
                    fetch(`${opts.apiProductosBuscar}?q=${encodeURIComponent(row.query)}&rfc=${opts.rfcUsuarioId}`)
                        .then(r => r.json()).then(list => {
                            if (list.length) {
                                const p = list[0];
                                row.descripcion    = p.descripcion || row.descripcion;
                                row.clave_prod_serv= p.clave_prod_serv || row.clave_prod_serv;
                                row.clave_unidad   = p.clave_unidad || row.clave_unidad;
                                row.unidad         = p.unidad || row.unidad;
                                row.precio         = p.precio ?? row.precio;
                            }
                        });
                },

                agregarRelacionado() {
                    this.form.relacionados.push({
                        uid: crypto.randomUUID?.() || String(Date.now() + Math.random()),
                        tipo_relacion: '',
                        uuid: '',
                    });
                },
                eliminarRelacionado(i) { this.form.relacionados.splice(i, 1); },

                previewFactura() {
                    // post a /facturacion/facturas/preview
                    // ...
                },
                guardarBorrador() {
                    // post a /facturacion/facturas/guardar
                    // ...
                },
                timbrarFactura() {
                    // validaciones + post timbrado
                    // ...
                },
            });
        });
    </script>
@endpush
