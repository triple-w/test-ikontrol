@extends('layouts.app')

@section('title', 'Nueva Factura')

@section('content')
@php
    // Normaliza datos enviados por el controlador
    $rfcUsuarioId = (int) ($rfcUsuarioId ?? 0);

    $clientesJson = collect($clientes ?? [])->map(function($c){
        return [
            'id'            => (int)$c->id,
            'rfc'           => (string)$c->rfc,
            'razon_social'  => (string)$c->razon_social,
            'calle'         => (string)($c->calle ?? ''),
            'no_ext'        => (string)($c->no_ext ?? ''),
            'no_int'        => (string)($c->no_int ?? ''),
            'colonia'       => (string)($c->colonia ?? ''),
            'localidad'     => (string)($c->localidad ?? ''),
            'estado'        => (string)($c->estado ?? ''),
            'codigo_postal' => (string)($c->codigo_postal ?? ''),
            'pais'          => (string)($c->pais ?? ''),
            'email'         => (string)($c->email ?? ''),
        ];
    })->values()->toJson();

    $serieDefault = $serieDefault ?? null;
@endphp

<div
    x-data="facturaForm({
        rfcUsuarioId: {{ $rfcUsuarioId }},
        clientes: {!! $clientesJson !!},
        minFecha: '{{ $minFecha }}',
        maxFecha: '{{ $maxFecha }}',
        serieDefault: @json($serieDefault),
        apiSeriesNext: '{{ url('/api/series/next') }}',
        apiProductosBuscar: '{{ url('/api/productos/buscar') }}',
        routeClienteUpdateBase: '{{ url('/catalogos/clientes') }}',
        routePreview: '{{ url('/facturacion/facturas/preview') }}',
        csrf: '{{ csrf_token() }}'
    })"
    class="p-4 space-y-6"  {{-- padding 16 en toda la pantalla --}}
>
    <!-- Encabezado -->
    <div class="flex items-start justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>

        <!-- Emisor activo (label arriba derecha) -->
        <div class="text-right">
            <div class="text-xs uppercase text-gray-400 dark:text-gray-500">RFC activo</div>
            <div class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="emisorLabel()"></div>
        </div>
    </div>

    <!-- Sección: Datos de la factura -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Tipo de comprobante -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Tipo de comprobante</label>
                <select x-model="fact.tipo"
                        @change="onTipoChange()"
                        class="form-select w-full">
                    <option value="I">Ingreso</option>
                    <option value="E">Egreso</option>
                    <option value="P">Pago</option>
                    <option value="N">Nómina</option>
                </select>
            </div>

            <!-- Serie -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Serie</label>
                <input type="text" x-model="fact.serie" class="form-input w-full" readonly>
            </div>

            <!-- Folio -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Folio</label>
                <input type="number" x-model.number="fact.folio" class="form-input w-full" readonly>
            </div>

            <!-- Fecha (máx ahora, mín -72h) -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fecha</label>
                <input type="datetime-local" x-model="fact.fecha"
                       :min="minFecha" :max="maxFecha"
                       class="form-input w-full">
            </div>

            <!-- Método de pago -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Método de pago</label>
                <select x-model="fact.metodo_pago" class="form-select w-full">
                    <option value="PUE">PUE - Pago en una sola exhibición</option>
                    <option value="PPD">PPD - Pago en parcialidades o diferido</option>
                </select>
            </div>

            <!-- Forma de pago (select) -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Forma de pago</label>
                <select x-model="fact.forma_pago" class="form-select w-full">
                    <option value="01">01 - Efectivo</option>
                    <option value="02">02 - Cheque nominativo</option>
                    <option value="03">03 - Transferencia electrónica</option>
                    <option value="04">04 - Tarjeta de crédito</option>
                    <option value="28">28 - Tarjeta de débito</option>
                    <option value="99">99 - Por definir</option>
                </select>
            </div>
        </div>

        <!-- Comentarios -->
        <div class="mt-4">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Comentario (PDF)</label>
            <textarea x-model="fact.comentario" rows="2" class="form-textarea w-full"></textarea>
        </div>
    </div>

    <!-- Sección: Cliente -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Cliente</div>
            <div class="flex items-center gap-2">
                <div class="w-64">
                    <select x-model.number="fact.cliente_id" @change="onClienteChange" class="form-select w-full">
                        <option value="">— Selecciona cliente —</option>
                        <template x-for="c in clientes" :key="c.id">
                            <option :value="c.id" x-text="c.razon_social + ' ('+c.rfc+')'"></option>
                        </template>
                    </select>
                </div>
                <button type="button" class="btn-sm bg-violet-500 text-white hover:bg-violet-600"
                        :disabled="!fact.cliente_id"
                        @click="openClienteModal()">
                    Actualizar cliente
                </button>
            </div>
        </div>

        <!-- Datos del cliente como labels, sin recuadro interno -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <div class="text-[11px] text-gray-400">RFC</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.rfc || '—'"></div>
            </div>
            <div>
                <div class="text-[11px] text-gray-400">Razón social</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.razon_social || '—'"></div>
            </div>
            <div>
                <div class="text-[11px] text-gray-400">C.P.</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.codigo_postal || '—'"></div>
            </div>
            <div class="md:col-span-3">
                <div class="text-[11px] text-gray-400">Domicilio</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100"
                     x-text="domicilioCliente()"></div>
            </div>
            <div>
                <div class="text-[11px] text-gray-400">Email</div>
                <div class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.email || '—'"></div>
            </div>
        </div>
    </div>

    <!-- Sección: Conceptos -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4">
        <div class="flex items-center justify-between mb-3">
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Conceptos</div>
            <button type="button" class="btn-sm bg-slate-700 text-white hover:bg-slate-800" @click="addConcepto()">Agregar concepto</button>
        </div>

        <!-- Buscador arriba (no se oculta) -->
        <div class="relative mb-3">
            <input type="text" class="form-input w-full" placeholder="Buscar producto (por descripción o clave)"
                   x-model.debounce.400ms="buscadorTerm"
                   @input="buscarProductos()">
            <!-- Resultados en dropdown absoluto -->
            <div class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow z-50"
                 x-show="showDropdown"
                 @click.outside="showDropdown = false"
                 x-transition>
                <template x-if="resultados.length === 0">
                    <div class="p-3 text-xs text-gray-500">Sin resultados…</div>
                </template>
                <template x-for="item in resultados" :key="item.id">
                    <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                            @click="onPickProducto(item)">
                        <div class="text-sm font-medium text-gray-800 dark:text-gray-100" x-text="item.descripcion"></div>
                        <div class="text-[11px] text-gray-500">
                            <span x-text="'$'+formatMoney(item.precio)"></span>
                            <span class="mx-1">•</span>
                            <span x-text="(item.clave_prod_serv_code || '-') + ' / ' + (item.clave_unidad_code || '-')"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>

        <!-- Tabla conceptos -->
        <div class="overflow-x-auto overflow-y-visible">
            <table class="table-auto min-w-full">
                <thead class="text-xs text-gray-500">
                    <tr>
                        <th class="px-2 py-2 text-left">Cant.</th>
                        <th class="px-2 py-2 text-left">Descripción</th>
                        <th class="px-2 py-2 text-left">Precio</th>
                        <th class="px-2 py-2 text-left">Cve ProdServ</th>
                        <th class="px-2 py-2 text-left">Cve Unidad</th>
                        <th class="px-2 py-2 text-left">Unidad</th>
                        <th class="px-2 py-2 text-left">Impuestos</th>
                        <th class="px-2 py-2 text-right">Importe</th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c,idx) in fact.conceptos" :key="idx">
                        <tr class="align-top">
                            <td class="px-2 py-2 w-20">
                                <input type="number" step="0.001" min="0" x-model.number="c.cantidad" @input="recalcular()" class="form-input w-full">
                            </td>
                            <td class="px-2 py-2">
                                <input type="text" x-model="c.descripcion" class="form-input w-full">
                            </td>
                            <td class="px-2 py-2 w-28">
                                <input type="number" step="0.01" min="0" x-model.number="c.valor_unitario" @input="recalcular()" class="form-input w-full">
                            </td>

                            <!-- Clave ProdServ (buscable) -->
                            <td class="px-2 py-2 w-36">
                                <div class="relative">
                                    <input type="text" x-model="c.clave_prod_serv_code" class="form-input w-full" placeholder="01010101"
                                           @input.debounce.400ms="buscarClaveProdServ(idx)">
                                    <div class="absolute left-0 top-full mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow z-50"
                                         x-show="c._showCPS"
                                         @click.outside="c._showCPS=false"
                                         x-transition>
                                        <template x-for="opt in c._cpsOpts" :key="opt.id">
                                            <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    @click="pickClaveProdServ(idx, opt)">
                                                <div class="text-sm font-medium" x-text="opt.code + ' — ' + opt.text"></div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <!-- Clave Unidad (buscable) -->
                            <td class="px-2 py-2 w-36">
                                <div class="relative">
                                    <input type="text" x-model="c.clave_unidad_code" class="form-input w-full" placeholder="H87"
                                           @input.debounce.400ms="buscarClaveUnidad(idx)">
                                    <div class="absolute left-0 top-full mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow z-50"
                                         x-show="c._showCU"
                                         @click.outside="c._showCU=false"
                                         x-transition>
                                        <template x-for="opt in c._cuOpts" :key="opt.id">
                                            <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    @click="pickClaveUnidad(idx, opt)">
                                                <div class="text-sm font-medium" x-text="opt.code + ' — ' + opt.text"></div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            <td class="px-2 py-2 w-28">
                                <input type="text" x-model="c.unidad" class="form-input w-full" placeholder="Pieza">
                            </td>

                            <!-- Impuestos por concepto -->
                            <td class="px-2 py-2 w-40">
                                <div class="space-y-1">
                                    <template x-for="(imp, j) in c.impuestos" :key="j">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[11px] px-1 rounded bg-slate-100 dark:bg-slate-700" x-text="imp.tipo + ' ' + imp.impuesto + ' ' + (imp.tasa*100) + '%'"></span>
                                            <button type="button" class="text-xs text-red-500 hover:underline" @click="removeImpuesto(idx, j)">Quitar</button>
                                        </div>
                                    </template>
                                    <button type="button" class="btn-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                                            @click="openImpuestoModal(idx)">
                                        + Agregar impuesto
                                    </button>
                                </div>
                            </td>

                            <td class="px-2 py-2 w-28 text-right">
                                <div class="tabular-nums" x-text="'$'+formatMoney(c.importe)"></div>
                            </td>
                            <td class="px-2 py-2 w-8 text-right">
                                <button type="button" class="text-red-500 hover:text-red-600" @click="removeConcepto(idx)">✕</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Impuestos globales (Locales / Frontera, etc.) -->
        <div class="mt-4">
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2">Impuestos globales (opcional)</div>
            <div class="flex flex-wrap gap-2">
                <template x-for="(igl, k) in fact.impuestos_globales" :key="k">
                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 rounded px-2 py-1">
                        <span class="text-xs" x-text="igl.nombre + ' ' + (igl.tasa*100) + '%'"></span>
                        <button type="button" class="text-xs text-red-500" @click="removeImpuestoGlobal(k)">Quitar</button>
                    </div>
                </template>
                <button type="button" class="btn-xs bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600"
                        @click="openImpuestoGlobalModal()">
                    + Agregar impuesto global
                </button>
            </div>
        </div>
    </div>

    <!-- Totales + acciones -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
            <div class="text-sm text-gray-500">
                <div>SubTotal: <span class="font-medium text-gray-800 dark:text-gray-100" x-text="'$'+formatMoney(totales.subtotal)"></span></div>
                <div>Impuestos trasladados: <span class="font-medium text-gray-800 dark:text-gray-100" x-text="'$'+formatMoney(totales.trasladados)"></span></div>
                <div>Impuestos retenidos: <span class="font-medium text-gray-800 dark:text-gray-100" x-text="'$'+formatMoney(totales.retenidos)"></span></div>
                <div class="text-lg mt-2">Total: <span class="font-semibold text-gray-900 dark:text-gray-50" x-text="'$'+formatMoney(totales.total)"></span></div>
            </div>
            <div class="text-right space-x-2">
                <button type="button" class="btn bg-white border border-gray-300 dark:bg-gray-700 dark:border-gray-600 hover:bg-gray-50"
                        @click="guardarPrefactura()" disabled>
                    Guardar como borrador
                </button>
                <button type="button" class="btn bg-slate-700 text-white hover:bg-slate-800"
                        @click="visualizar()">
                    Visualizar
                </button>
                <button type="button" class="btn bg-violet-600 text-white hover:bg-violet-700" disabled>
                    Timbrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal lateral: actualizar cliente -->
    <div class="fixed inset-0 z-50" x-show="clienteModal.open" x-cloak>
        <div class="absolute inset-0 bg-black/40" @click="closeClienteModal()"></div>
        <div class="absolute right-0 top-0 h-full w-full sm:w-[480px] bg-white dark:bg-gray-800 shadow-xl p-4 overflow-y-auto"
             x-trap.noscroll.inert="clienteModal.open"
             x-transition:enter="transition ease-out duration-200 transform"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-150 transform"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0">
            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold">Actualizar cliente</div>
                <button class="text-gray-500 hover:text-gray-700" @click="closeClienteModal()">✕</button>
            </div>

            <template x-if="clienteModal.data">
                <form :action="routeClienteUpdate(clienteModal.data.id)" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs text-gray-500">RFC</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.rfc">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Razón social</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.razon_social">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Calle</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.calle">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">No. Ext</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.no_ext">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">No. Int</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.no_int">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Colonia</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.colonia">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Localidad</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.localidad">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Estado</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.estado">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">C.P.</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.codigo_postal">
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">País</label>
                            <input type="text" class="form-input w-full" x-model="clienteModal.data.pais">
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs text-gray-500">Email</label>
                            <input type="email" class="form-input w-full" x-model="clienteModal.data.email">
                        </div>
                    </div>

                    <div class="pt-2 text-right">
                        <button type="button" class="btn bg-white border border-gray-300 hover:bg-gray-50" @click="closeClienteModal()">Cancelar</button>
                        <button type="submit" class="btn bg-violet-600 text-white hover:bg-violet-700">Guardar</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

    <!-- Modal mini: impuesto por concepto -->
    <div class="fixed inset-0 z-50" x-show="impModal.open" x-cloak>
        <div class="absolute inset-0 bg-black/40" @click="impModal.open=false"></div>
        <div class="absolute left-1/2 top-1/2 w-[360px] -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-gray-800 rounded shadow p-4">
            <div class="text-sm font-semibold mb-2">Agregar impuesto</div>
            <div class="space-y-2">
                <div>
                    <label class="text-xs text-gray-500">Tipo</label>
                    <select class="form-select w-full" x-model="impModal.tipo">
                        <option value="Traslado">Traslado</option>
                        <option value="Retención">Retención</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Impuesto</label>
                    <select class="form-select w-full" x-model="impModal.impuesto">
                        <option value="001">001 - ISR</option>
                        <option value="002">002 - IVA</option>
                        <option value="003">003 - IEPS</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500">Tasa (%)</label>
                    <input type="number" step="0.001" min="0" class="form-input w-full" x-model.number="impModal.tasaPct">
                </div>
            </div>
            <div class="mt-3 text-right">
                <button class="btn bg-white border border-gray-300 hover:bg-gray-50" @click="impModal.open=false">Cancelar</button>
                <button class="btn bg-violet-600 text-white hover:bg-violet-700" @click="confirmImpuesto()">Agregar</button>
            </div>
        </div>
    </div>

    <!-- Modal mini: impuesto global -->
    <div class="fixed inset-0 z-50" x-show="impGModal.open" x-cloak>
        <div class="absolute inset-0 bg-black/40" @click="impGModal.open=false"></div>
        <div class="absolute left-1/2 top-1/2 w-[360px] -translate-x-1/2 -translate-y-1/2 bg-white dark:bg-gray-800 rounded shadow p-4">
            <div class="text-sm font-semibold mb-2">Agregar impuesto global</div>
            <div class="space-y-2">
                <div>
                    <label class="text-xs text-gray-500">Nombre</label>
                    <input type="text" class="form-input w-full" x-model="impGModal.nombre" placeholder="Ej. Imp. cedular local">
                </div>
                <div>
                    <label class="text-xs text-gray-500">Tasa (%)</label>
                    <input type="number" step="0.001" min="0" class="form-input w-full" x-model.number="impGModal.tasaPct">
                </div>
            </div>
            <div class="mt-3 text-right">
                <button class="btn bg-white border border-gray-300 hover:bg-gray-50" @click="impGModal.open=false">Cancelar</button>
                <button class="btn bg-violet-600 text-white hover:bg-violet-700" @click="confirmImpuestoGlobal()">Agregar</button>
            </div>
        </div>
    </div>

</div>

{{-- ================== Alpine helpers ================== --}}
<script>
window.facturaForm = function initFacturaForm(cfg) {
    return {
        // CONFIG
        rfcUsuarioId: cfg.rfcUsuarioId,
        clientes: cfg.clientes || [],
        minFecha: cfg.minFecha,
        maxFecha: cfg.maxFecha,
        apiSeriesNext: cfg.apiSeriesNext,
        apiProductosBuscar: cfg.apiProductosBuscar,
        routeClienteUpdateBase: cfg.routeClienteUpdateBase,
        routePreview: cfg.routePreview,
        csrf: cfg.csrf,
        serieDefault: cfg.serieDefault || null,

        // STATE
        fact: {
            tipo: 'I',
            serie: '',
            folio: null,
            fecha: cfg.maxFecha,    // default ahora
            metodo_pago: 'PUE',
            forma_pago: '99',
            comentario: '',
            cliente_id: '',
            conceptos: [],
            impuestos_globales: [],
        },
        clienteSel: {},

        // buscador productos (arriba)
        buscadorTerm: '',
        resultados: [],
        showDropdown: false,

        // modales
        clienteModal: { open: false, data: null },
        impModal:     { open: false, idx: null, tipo: 'Traslado', impuesto: '002', tasaPct: 16 },
        impGModal:    { open: false, nombre: '', tasaPct: 0 },

        totales: { subtotal: 0, trasladados: 0, retenidos: 0, total: 0 },

        // INIT
        async init() {
            // Serie/folio por defecto (Ingreso)
            if (this.serieDefault && this.serieDefault.serie) {
                this.fact.serie = this.serieDefault.serie;
                this.fact.folio = this.serieDefault.folio;
            } else {
                await this.onTipoChange(); // intenta fetch
            }
            this.recalcular();
        },

        // UI helpers
        emisorLabel() {
            // Solo visualizar el RFC activo (lo trae el layout), aquí placeholder
            return 'RFC seleccionado en el header';
        },
        domicilioCliente() {
            const c = this.clienteSel || {};
            const p1 = [c.calle, c.no_ext, c.no_int].filter(Boolean).join(' ');
            const p2 = [c.colonia, c.localidad, c.estado].filter(Boolean).join(', ');
            return [p1, p2, c.pais].filter(Boolean).join(' · ');
        },
        formatMoney(n) {
            return Number(n || 0).toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        },

        // Serie / Folio
        async onTipoChange() {
            try {
                const url = `${this.apiSeriesNext}?tipo=${encodeURIComponent(this.fact.tipo)}`;
                const r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
                const j = await r.json();
                if (j && j.ok) {
                    this.fact.serie = j.serie || '';
                    this.fact.folio = j.folio || null;
                } else {
                    this.fact.serie = '';
                    this.fact.folio = null;
                    console.warn(j?.message || 'Sin serie/folio configurado');
                }
            } catch(e) {
                console.error(e);
                this.fact.serie = '';
                this.fact.folio = null;
            }
        },

        // Cliente
        onClienteChange() {
            const id = Number(this.fact.cliente_id);
            this.clienteSel = this.clientes.find(c => c.id === id) || {};
        },
        openClienteModal() {
            if (!this.fact.cliente_id) return;
            this.clienteModal.data = JSON.parse(JSON.stringify(this.clienteSel));
            this.clienteModal.open = true;
        },
        closeClienteModal() {
            this.clienteModal.open = false;
            this.clienteModal.data = null;
        },
        routeClienteUpdate(id) {
            return `${this.routeClienteUpdateBase}/${id}`;
        },

        // Conceptos
        addConcepto() {
            this.fact.conceptos.push({
                cantidad: 1,
                descripcion: '',
                valor_unitario: 0,
                clave_prod_serv_id: null,
                clave_unidad_id: null,
                clave_prod_serv_code: '',
                clave_unidad_code: '',
                unidad: '',
                impuestos: [],
                importe: 0,
                // dropdowns internos para claves
                _showCPS: false, _cpsOpts: [],
                _showCU: false,  _cuOpts:  [],
            });
        },
        removeConcepto(idx) {
            this.fact.conceptos.splice(idx,1);
            this.recalcular();
        },

        // Buscador productos (arriba)
        async buscarProductos() {
            const term = (this.buscadorTerm || '').trim();
            if (!term) { this.resultados = []; this.showDropdown=false; return; }
            try {
                const url = `${this.apiProductosBuscar}?term=${encodeURIComponent(term)}`;
                const r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
                const j = await r.json();
                this.resultados = Array.isArray(j.items) ? j.items : [];
                this.showDropdown = true;
            } catch(e) {
                console.error(e);
                this.resultados = [];
                this.showDropdown = false;
            }
        },
        onPickProducto(item) {
            this.showDropdown = false;
            // Rellena en un concepto nuevo
            const c = {
                cantidad: 1,
                descripcion: item.descripcion || '',
                valor_unitario: Number(item.precio || 0),
                clave_prod_serv_id: item.clave_prod_serv_id || null,
                clave_unidad_id: item.clave_unidad_id || null,
                clave_prod_serv_code: item.clave_prod_serv_code || '',
                clave_unidad_code: item.clave_unidad_code || '',
                unidad: item.unidad || '',
                impuestos: [],
                importe: 0,
                _showCPS: false, _cpsOpts: [],
                _showCU: false,  _cuOpts:  [],
            };
            this.fact.conceptos.push(c);
            this.recalcular();
            // Limpia el buscador
            this.buscadorTerm = '';
            this.resultados = [];
        },

        // Buscadores de claves SAT (mock local por ahora)
        async buscarClaveProdServ(idx) {
            const c = this.fact.conceptos[idx];
            const term = (c.clave_prod_serv_code || '').trim();
            if (!term) { c._cpsOpts=[]; c._showCPS=false; return; }
            // TODO: Cambiar a endpoint real si tienes catálogo SAT
            // De momento, devolvemos algunas opciones dummy:
            c._cpsOpts = [
                {id: 1, code: '01010101', text: 'No existe en el catálogo'},
                {id: 2, code: '44103104', text: 'Papel térmico para ticket'},
                {id: 3, code: '43231512', text: 'Servicios de software'}
            ].filter(o => (o.code.includes(term) || o.text.toLowerCase().includes(term.toLowerCase()))).slice(0,8);
            c._showCPS = c._cpsOpts.length > 0;
        },
        pickClaveProdServ(idx, opt) {
            const c = this.fact.conceptos[idx];
            c.clave_prod_serv_id = opt.id; // si luego mapeas a id real
            c.clave_prod_serv_code = opt.code;
            c._showCPS = false;
        },
        async buscarClaveUnidad(idx) {
            const c = this.fact.conceptos[idx];
            const term = (c.clave_unidad_code || '').trim();
            if (!term) { c._cuOpts=[]; c._showCU=false; return; }
            // Dummy opciones
            c._cuOpts = [
                {id: 1, code: 'H87', text: 'Pieza'},
                {id: 2, code: 'E48', text: 'Unidad de servicio'},
                {id: 3, code: 'KGM', text: 'Kilogramo'}
            ].filter(o => (o.code.includes(term) || o.text.toLowerCase().includes(term.toLowerCase()))).slice(0,8);
            c._showCU = c._cuOpts.length > 0;
        },
        pickClaveUnidad(idx, opt) {
            const c = this.fact.conceptos[idx];
            c.clave_unidad_id = opt.id; // si luego mapeas a id real
            c.clave_unidad_code = opt.code;
            c.unidad = opt.text;
            c._showCU = false;
        },

        // Impuestos por concepto
        openImpuestoModal(idx) {
            this.impModal.open = true;
            this.impModal.idx  = idx;
            this.impModal.tipo = 'Traslado';
            this.impModal.impuesto = '002';
            this.impModal.tasaPct = 16;
        },
        confirmImpuesto() {
            const idx = this.impModal.idx;
            if (idx === null) return;
            const tasa = Number(this.impModal.tasaPct || 0)/100;
            this.fact.conceptos[idx].impuestos.push({
                tipo: this.impModal.tipo,       // Traslado | Retención
                impuesto: this.impModal.impuesto, // 001|002|003
                tasa: tasa
            });
            this.impModal.open = false;
            this.recalcular();
        },
        removeImpuesto(idx, j) {
            this.fact.conceptos[idx].impuestos.splice(j,1);
            this.recalcular();
        },

        // Impuestos globales
        openImpuestoGlobalModal() {
            this.impGModal.open = true;
            this.impGModal.nombre = '';
            this.impGModal.tasaPct = 0;
        },
        confirmImpuestoGlobal() {
            const t = Number(this.impGModal.tasaPct || 0)/100;
            this.fact.impuestos_globales.push({
                nombre: this.impGModal.nombre || 'Impuesto local',
                tasa: t
            });
            this.impGModal.open = false;
            this.recalcular();
        },
        removeImpuestoGlobal(k) {
            this.fact.impuestos_globales.splice(k,1);
            this.recalcular();
        },

        // Cálculos
        recalcular() {
            let subtotal = 0, tras = 0, ret = 0;
            this.fact.conceptos.forEach(c => {
                const impBase = Number(c.cantidad||0) * Number(c.valor_unitario||0);
                c.importe = impBase;
                subtotal += impBase;

                (c.impuestos||[]).forEach(imp => {
                    const m = impBase * Number(imp.tasa||0);
                    if (imp.tipo === 'Retención') ret += m;
                    else tras += m;
                });
            });

            // Impuestos globales (aplicados al subtotal)
            (this.fact.impuestos_globales||[]).forEach(igl => {
                const m = subtotal * Number(igl.tasa||0);
                // Asumimos trasladados (puedes dividir en otra UI si quieres retenciones globales)
                tras += m;
            });

            const total = subtotal + tras - ret;
            this.totales = { subtotal, trasladados: tras, retenidos: ret, total };
        },

        // Preview
        async visualizar() {
            try {
                const payload = JSON.stringify(this.fact);
                const r = await fetch(this.routePreview, {
                    method: 'POST',
                    headers: {
                        'Content-Type':'application/x-www-form-urlencoded',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With':'XMLHttpRequest',
                    },
                    body: new URLSearchParams({ data: payload })
                });
                const html = await r.text();
                const win = window.open('', '_blank');
                win.document.open();
                win.document.write(html);
                win.document.close();
            } catch(e) {
                console.error(e);
                alert('No fue posible generar la visualización.');
            }
        },

        guardarPrefactura(){ /* listo para implementar */ },
    }
}
</script>
@endsection
