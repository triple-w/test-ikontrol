@extends('layouts.app')

@section('title', 'Nueva Factura')

@section('content')
@php
    // Serializamos clientes para Alpine
    $clientesJson = collect($clientes)->map(function ($c) {
        return [
            'id' => $c->id,
            'rfc' => $c->rfc,
            'razon_social' => $c->razon_social,
            'calle' => $c->calle,
            'no_ext' => $c->no_ext,
            'no_int' => $c->no_int,
            'colonia' => $c->colonia,
            'localidad' => $c->localidad,
            'estado' => $c->estado,
            'codigo_postal' => $c->codigo_postal,
            'pais' => $c->pais,
            'email' => $c->email,
        ];
    })->values()->toJson();
@endphp

<div
    x-data="facturaForm({
        rfcUsuarioId: {{ (int) $rfcUsuarioId }},
        clientes: {!! $clientesJson !!},
        minFecha: '{{ $minFecha }}',
        maxFecha: '{{ $maxFecha }}',
        defaultSerie: '{{ $defaultSerie }}',
        defaultFolio: '{{ $defaultFolio }}',
        apiSeriesNext: '{{ url('/api/series/next') }}',
        apiProductosBuscar: '{{ url('/api/productos/buscar') }}',
        routeClienteUpdateBase: '{{ url('/catalogos/clientes') }}', // PUT /{id}
        csrf: '{{ csrf_token() }}'
    })"
    class="px-4 sm:px-6 lg:px-8 py-8 space-y-6"
>
    {{-- Título + RFC activo (arriba derecha) --}}
    <div class="flex items-start justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>
        <div class="text-right">
            <div class="text-xs uppercase text-gray-400 dark:text-gray-500">RFC activo</div>
            <div class="text-sm font-semibold text-gray-700 dark:text-gray-200" x-text="encabezado.emisor_rfc"></div>
        </div>
    </div>

    {{-- Card: Datos del comprobante --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tipo de comprobante</label>
                <select class="form-select w-full" x-model="encabezado.tipo" @change="cargarSerieFolio()">
                    <option value="I">Ingreso</option>
                    <option value="E">Egreso (Nota de crédito)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Serie</label>
                <input type="text" class="form-input w-full" x-model="encabezado.serie" readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Folio</label>
                <input type="text" class="form-input w-full" x-model="encabezado.folio" readonly>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Fecha</label>
                <input type="datetime-local" class="form-input w-full" x-model="encabezado.fecha" :min="minFecha" :max="maxFecha">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Moneda</label>
                <select class="form-select w-full" x-model="encabezado.moneda">
                    <option value="MXN">MXN</option>
                    <option value="USD">USD</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Método de Pago</label>
                <select class="form-select w-full" x-model="encabezado.metodo_pago">
                    <option value="PUE">PUE</option>
                    <option value="PPD">PPD</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Forma de Pago</label>
                <select class="form-select w-full" x-model="encabezado.forma_pago">
                    <option value="">—</option>
                    <option value="01">01 - Efectivo</option>
                    <option value="02">02 - Cheque</option>
                    <option value="03">03 - Transferencia</option>
                    <option value="04">04 - Tarjeta de crédito</option>
                    <option value="28">28 - Tarjeta de débito</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Comentario (PDF)</label>
                <textarea class="form-input w-full" rows="2" x-model="encabezado.comentarios_pdf" placeholder="Opcional"></textarea>
            </div>
        </div>
    </div>

    {{-- Card: Cliente --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-full md:w-2/3">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
                <select class="form-select w-full" x-model.number="clienteSeleccionadoId" @change="onSelectCliente()">
                    <option value="">— Selecciona —</option>
                    <template x-for="c in clientes" :key="c.id">
                        <option :value="c.id" x-text="`${c.razon_social} — ${c.rfc}`"></option>
                    </template>
                </select>
            </div>
            <div class="ml-4">
                <button type="button" @click="abrirDrawerCliente()" class="btn-sm bg-violet-500 hover:bg-violet-600 text-white">
                    Editar cliente
                </button>
            </div>
        </div>

        {{-- Labels del cliente (sin recuadro extra) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 text-sm">
            <div><span class="text-gray-500">RFC:</span> <span class="font-medium" x-text="cliente.rfc || '—'"></span></div>
            <div><span class="text-gray-500">Razón social:</span> <span class="font-medium" x-text="cliente.razon_social || '—'"></span></div>
            <div><span class="text-gray-500">CP:</span> <span class="font-medium" x-text="cliente.codigo_postal || '—'"></span></div>
            <div class="sm:col-span-2"><span class="text-gray-500">Calle y número:</span>
                <span class="font-medium" x-text="`${cliente.calle ?? ''} ${cliente.no_ext ?? ''} ${cliente.no_int ?? ''}`.trim() || '—'"></span>
            </div>
            <div><span class="text-gray-500">Colonia:</span> <span class="font-medium" x-text="cliente.colonia || '—'"></span></div>
            <div><span class="text-gray-500">Localidad:</span> <span class="font-medium" x-text="cliente.localidad || '—'"></span></div>
            <div><span class="text-gray-500">Estado:</span> <span class="font-medium" x-text="cliente.estado || '—'"></span></div>
            <div><span class="text-gray-500">País:</span> <span class="font-medium" x-text="cliente.pais || '—'"></span></div>
            <div class="sm:col-span-2"><span class="text-gray-500">Email:</span> <span class="font-medium" x-text="cliente.email || '—'"></span></div>
        </div>
    </div>

    {{-- Card: Conceptos --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div class="w-full md:w-2/3">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Buscar y agregar producto</label>
                <div class="relative">
                    <input type="text" class="form-input w-full" placeholder="Escribe para buscar…" x-model="buscadorProducto"
                           @input.debounce.300ms="buscarProducto()">
                    <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 rounded-lg shadow"
                         x-show="resultadosProductos.length > 0" @click.outside="resultadosProductos=[]" x-cloak>
                        <ul class="max-h-64 overflow-auto">
                            <template x-for="p in resultadosProductos" :key="p.id">
                                <li>
                                    <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                            @click="agregarProductoDesdeBusqueda(p)">
                                        <div class="text-sm font-medium" x-text="p.descripcion"></div>
                                        <div class="text-xs text-gray-500">
                                            <span x-text="`$${Number(p.precio).toFixed(2)}`"></span> ·
                                            <span x-text="`Clave ProdServ: ${p.clave_prod_serv_id ?? '—'} | Unidad: ${p.unidad ?? '—'}`"></span>
                                        </div>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="ml-4">
                <button type="button" class="btn-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                        @click="agregarConceptoManual()">
                    Agregar concepto vacío
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="table-auto w-full text-sm">
                <thead class="text-xs text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-2 py-2 text-left">Descripción</th>
                        <th class="px-2 py-2">Cant.</th>
                        <th class="px-2 py-2">Precio</th>
                        <th class="px-2 py-2">Clave ProdServ</th>
                        <th class="px-2 py-2">Clave Unidad</th>
                        <th class="px-2 py-2">Unidad</th>
                        <th class="px-2 py-2">Impuestos</th>
                        <th class="px-2 py-2">Importe</th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, idx) in conceptos" :key="c.__key">
                        <tr class="border-t border-gray-200 dark:border-gray-700/60">
                            <td class="px-2 py-2">
                                <input type="text" class="form-input w-full" x-model="c.descripcion" placeholder="Descripción">
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" step="0.001" class="form-input w-24 text-right" x-model.number="c.cantidad" @input="recalcular()">
                            </td>
                            <td class="px-2 py-2">
                                <input type="number" step="0.01" class="form-input w-28 text-right" x-model.number="c.precio" @input="recalcular()">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" class="form-input w-36" x-model="c.clave_prod_serv_id" placeholder="Clave SAT">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" class="form-input w-28" x-model="c.clave_unidad_id" placeholder="Clave">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" class="form-input w-24" x-model="c.unidad" placeholder="Unidad">
                            </td>
                            <td class="px-2 py-2">
                                <button type="button" class="btn-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                                        @click="abrirImpuestos(idx)">
                                    Editar
                                </button>
                            </td>
                            <td class="px-2 py-2 text-right">
                                <span x-text="formatoMoneda(c.cantidad * c.precio)"></span>
                            </td>
                            <td class="px-2 py-2 text-right">
                                <button type="button" class="text-red-500 hover:text-red-600" @click="eliminarConcepto(idx)">Eliminar</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Totales simples (sin impuestos locales aún) --}}
        <div class="flex justify-end">
            <div class="text-right space-y-1">
                <div><span class="text-gray-500">Subtotal: </span><span class="font-semibold" x-text="formatoMoneda(totales.subtotal)"></span></div>
                <div><span class="text-gray-500">Impuestos: </span><span class="font-semibold" x-text="formatoMoneda(totales.impuestos)"></span></div>
                <div class="text-lg"><span class="text-gray-500">Total: </span><span class="font-bold" x-text="formatoMoneda(totales.total)"></span></div>
            </div>
        </div>
    </div>

    {{-- Card: Documentos relacionados --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Documentos relacionados</h3>
            <button type="button" class="btn-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                    @click="agregarRelacion()">Agregar</button>
        </div>
        <div class="space-y-2">
            <template x-for="(r, i) in relaciones" :key="r.__key">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                    <div class="md:col-span-2">
                        <input class="form-input w-full" placeholder="UUID" x-model="r.uuid">
                    </div>
                    <div>
                        <select class="form-select w-full" x-model="r.tipo_relacion">
                            <option value="">— Tipo —</option>
                            <option value="01">01 - Nota de crédito</option>
                            <option value="04">04 - Sustitución</option>
                            <option value="07">07 - CFDI por aplicación de anticipo</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <input class="form-input w-full" placeholder="Folio interno (opcional)" x-model="r.folio_ref">
                    </div>
                    <div class="text-right">
                        <button type="button" class="text-red-500 hover:text-red-600" @click="eliminarRelacion(i)">Eliminar</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('facturas.index') }}" class="btn border border-gray-300 dark:border-gray-600">Cancelar</a>
        <button type="button" class="btn bg-violet-500 hover:bg-violet-600 text-white" @click="previsualizar()">
            Previsualizar
        </button>
    </div>

    {{-- Drawer lateral: Editar Cliente --}}
    <div class="fixed inset-0 z-50" x-show="drawerCliente" x-cloak>
        <div class="absolute inset-0 bg-gray-900/40" @click="drawerCliente=false"></div>
        <div class="absolute top-0 right-0 h-full w-full sm:w-[420px] bg-white dark:bg-gray-800 shadow-xl p-4 sm:p-6 overflow-y-auto"
             x-trap.noscroll.inert="drawerCliente">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Editar cliente</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="drawerCliente=false">&times;</button>
            </div>
            <template x-if="clienteSeleccionadoId">
                <form @submit.prevent="guardarCliente()">
                    <div class="grid grid-cols-1 gap-3">
                        <div>
                            <label class="text-xs text-gray-500">Razón social</label>
                            <input class="form-input w-full" x-model="clienteEdit.razon_social">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">RFC</label>
                            <input class="form-input w-full" x-model="clienteEdit.rfc">
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs text-gray-500">Calle</label>
                                <input class="form-input w-full" x-model="clienteEdit.calle">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">No. ext</label>
                                <input class="form-input w-full" x-model="clienteEdit.no_ext">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">No. int</label>
                                <input class="form-input w-full" x-model="clienteEdit.no_int">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-xs text-gray-500">Colonia</label>
                                <input class="form-input w-full" x-model="clienteEdit.colonia">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Localidad</label>
                                <input class="form-input w-full" x-model="clienteEdit.localidad">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="text-xs text-gray-500">Estado</label>
                                <input class="form-input w-full" x-model="clienteEdit.estado">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">CP</label>
                                <input class="form-input w-full" x-model="clienteEdit.codigo_postal">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">País</label>
                                <input class="form-input w-full" x-model="clienteEdit.pais">
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Email</label>
                            <input class="form-input w-full" x-model="clienteEdit.email">
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" class="btn" @click="drawerCliente=false">Cerrar</button>
                        <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">Guardar</button>
                    </div>
                </form>
            </template>
            <template x-if="!clienteSeleccionadoId">
                <div class="text-sm text-gray-500">Selecciona un cliente primero.</div>
            </template>
        </div>
    </div>

    {{-- Drawer lateral: Impuestos por concepto --}}
    <div class="fixed inset-0 z-50" x-show="drawerImpuestos" x-cloak>
        <div class="absolute inset-0 bg-gray-900/40" @click="drawerImpuestos=false"></div>
        <div class="absolute top-0 right-0 h-full w-full sm:w-[420px] bg-white dark:bg-gray-800 shadow-xl p-4 sm:p-6 overflow-y-auto"
             x-trap.noscroll.inert="drawerImpuestos">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Impuestos del concepto</h3>
                <button class="text-gray-400 hover:text-gray-600" @click="drawerImpuestos=false">&times;</button>
            </div>

            <template x-if="indiceImpuestos !== null">
                <div class="space-y-4">
                    <div>
                        <button type="button" class="btn-sm bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                                @click="agregarImpuesto()">
                            Agregar impuesto
                        </button>
                    </div>
                    <template x-for="(imp, i) in conceptos[indiceImpuestos].impuestos" :key="imp.__key">
                        <div class="border border-gray-200 dark:border-gray-700/60 rounded-lg p-3">
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="text-xs text-gray-500">Tipo</label>
                                    <select class="form-select w-full" x-model="imp.tipo">
                                        <option value="Traslado">Traslado</option>
                                        <option value="Retencion">Retención</option>
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
                                    <label class="text-xs text-gray-500">Tasa</label>
                                    <select class="form-select w-full" x-model.number="imp.tasa" @change="recalcular()">
                                        <option :value="0.0">0%</option>
                                        <option :value="0.08">8%</option>
                                        <option :value="0.16">16%</option>
                                        <option :value="0.106667">ISR 10.6667%</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-right mt-2">
                                <button type="button" class="text-red-500 hover:text-red-600" @click="eliminarImpuesto(i)">Eliminar</button>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- Alpine factory en línea (no re-registra plugins, sin $persist para evitar el error) --}}
<script>
window.facturaForm = function (opts) {
    return {
        // props
        rfcUsuarioId: opts.rfcUsuarioId,
        clientes: opts.clientes || [],
        minFecha: opts.minFecha,
        maxFecha: opts.maxFecha,
        apiSeriesNext: opts.apiSeriesNext,
        apiProductosBuscar: opts.apiProductosBuscar,
        routeClienteUpdateBase: opts.routeClienteUpdateBase,
        csrf: opts.csrf,

        // UI state
        drawerCliente: false,
        drawerImpuestos: false,
        indiceImpuestos: null,

        // encabezado
        encabezado: {
            tipo: 'I',
            serie: opts.defaultSerie || '',
            folio: opts.defaultFolio || '',
            fecha: opts.maxFecha,
            moneda: 'MXN',
            metodo_pago: 'PUE',
            forma_pago: '',
            comentarios_pdf: '',
            emisor_rfc: (document.querySelector('.italic')?.innerText || '') // opcional, si ya lo muestras en header
        },

        // cliente
        clienteSeleccionadoId: '',
        cliente: {},
        clienteEdit: {},

        // conceptos
        conceptos: [],
        buscadorProducto: '',
        resultadosProductos: [],

        // relaciones
        relaciones: [],

        // totales
        totales: { subtotal: 0, impuestos: 0, total: 0 },

        init() {
            // set defaults serie/folio a la carga (I)
            this.cargarSerieFolio();
        },

        cargarSerieFolio() {
            fetch(`${this.apiSeriesNext}?tipo=${this.encabezado.tipo}&rfc_usuario_id=${this.rfcUsuarioId}`, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    this.encabezado.serie = d.serie || '';
                    this.encabezado.folio = d.folio || '';
                })
                .catch(() => {});
        },

        onSelectCliente() {
            const c = this.clientes.find(x => String(x.id) === String(this.clienteSeleccionadoId));
            this.cliente = c ? JSON.parse(JSON.stringify(c)) : {};
            this.clienteEdit = JSON.parse(JSON.stringify(this.cliente));
        },

        abrirDrawerCliente() {
            if (!this.clienteSeleccionadoId) return;
            this.clienteEdit = JSON.parse(JSON.stringify(this.cliente));
            this.drawerCliente = true;
        },

        guardarCliente() {
            if (!this.clienteSeleccionadoId) return;

            fetch(`${this.routeClienteUpdateBase}/${this.clienteSeleccionadoId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrf,
                    'Accept': 'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.clienteEdit),
            })
            .then(r => r.ok ? r.json().catch(()=> ({})) : Promise.reject())
            .then(() => {
                // Refresca datos locales
                this.cliente = JSON.parse(JSON.stringify(this.clienteEdit));
                const idx = this.clientes.findIndex(x => x.id === this.clienteSeleccionadoId);
                if (idx >= 0) this.clientes[idx] = JSON.parse(JSON.stringify(this.clienteEdit));
                this.drawerCliente = false;
            })
            .catch(() => {
                alert('No se pudo guardar el cliente (revisa validaciones).');
            });
        },

        // Productos
        buscarProducto() {
            const q = this.buscadorProducto.trim();
            if (q.length < 2) { this.resultadosProductos = []; return; }
            fetch(`${this.apiProductosBuscar}?q=${encodeURIComponent(q)}&rfc_usuario_id=${this.rfcUsuarioId}`, { credentials:'same-origin' })
                .then(r => r.json())
                .then(list => { this.resultadosProductos = list || []; })
                .catch(()=>{ this.resultadosProductos = []; });
        },

        agregarProductoDesdeBusqueda(p) {
            this.resultadosProductos = [];
            this.buscadorProducto = '';
            this.conceptos.push({
                __key: crypto.randomUUID(),
                descripcion: p.descripcion || '',
                cantidad: 1,
                precio: parseFloat(p.precio || 0),
                clave_prod_serv_id: p.clave_prod_serv_id || '',
                clave_unidad_id: p.clave_unidad_id || '',
                unidad: p.unidad || '',
                impuestos: [],
            });
            this.recalcular();
        },

        agregarConceptoManual() {
            this.conceptos.push({
                __key: crypto.randomUUID(),
                descripcion: '',
                cantidad: 1,
                precio: 0,
                clave_prod_serv_id: '',
                clave_unidad_id: '',
                unidad: '',
                impuestos: [],
            });
            this.recalcular();
        },

        eliminarConcepto(i) {
            this.conceptos.splice(i, 1);
            this.recalcular();
        },

        abrirImpuestos(i) {
            this.indiceImpuestos = i;
            this.drawerImpuestos = true;
        },

        agregarImpuesto() {
            if (this.indiceImpuestos === null) return;
            this.conceptos[this.indiceImpuestos].impuestos.push({
                __key: crypto.randomUUID(),
                tipo: 'Traslado',
                impuesto: 'IVA',
                tasa: 0.16,
            });
            this.recalcular();
        },

        eliminarImpuesto(i) {
            if (this.indiceImpuestos === null) return;
            this.conceptos[this.indiceImpuestos].impuestos.splice(i,1);
            this.recalcular();
        },

        // Relaciones
        agregarRelacion() {
            this.relaciones.push({ __key: crypto.randomUUID(), uuid: '', tipo_relacion: '', folio_ref: '' });
        },
        eliminarRelacion(i) {
            this.relaciones.splice(i, 1);
        },

        // Totales (simple)
        recalcular() {
            let subtotal = 0, impuestos = 0;

            this.conceptos.forEach(c => {
                const base = (Number(c.cantidad) || 0) * (Number(c.precio) || 0);
                subtotal += base;

                (c.impuestos || []).forEach(imp => {
                    const t = Number(imp.tasa) || 0;
                    if (imp.tipo === 'Traslado') impuestos += base * t;
                    if (imp.tipo === 'Retencion') impuestos -= base * t;
                });
            });

            this.totales.subtotal = +subtotal.toFixed(2);
            this.totales.impuestos = +impuestos.toFixed(2);
            this.totales.total = +(subtotal + impuestos).toFixed(2);
        },

        formatoMoneda(n) {
            const num = Number(n || 0);
            return num.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        },

        // Preview
        previsualizar() {
            // Validaciones mínimas
            if (!this.clienteSeleccionadoId) { alert('Selecciona un cliente.'); return; }
            if (this.conceptos.length === 0) { alert('Agrega al menos un concepto.'); return; }
            this.recalcular();

            const payload = {
                encabezado: this.encabezado,
                cliente: this.cliente,
                conceptos: this.conceptos,
                relaciones: this.relaciones,
                totales: this.totales,
                _token: this.csrf,
            };

            // form post a /facturacion/facturas/preview
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ url('/facturacion/facturas/preview') }}';

            for (const [k, v] of Object.entries(payload)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = typeof v === 'string' ? v : JSON.stringify(v);
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        },
    }
}
</script>
@endsection
