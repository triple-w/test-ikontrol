@php
    // Limitar fecha a últimas 72 horas
    $now = now();
    $maxFecha = $now->format('Y-m-d\TH:i');
    $minFecha = $now->copy()->subHours(72)->format('Y-m-d\TH:i');

    // Helper pequeño para pintar labels de cliente
    $clientesJson = $clientes->map(function ($c) {
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
        apiSeriesNext: '{{ url('/api/series/next') }}',
        apiProductosBuscar: '{{ url('/api/productos/buscar') }}',
        routeClienteUpdateBase: '{{ url('/catalogos/clientes') }}', // PUT /{id}
        csrf: '{{ csrf_token() }}'
    })"
    class="space-y-6"
>

    <!-- Título + Emisor (arriba derecha) -->
    <div class="flex items-start justify-between">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>
        <div class="text-right">
            <div class="text-xs uppercase text-gray-400 dark:text-gray-500">Emisor (RFC activo)</div>
            <div class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $rfcActivo }}</div>
        </div>
    </div>

    <!-- Tarjeta principal -->
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5 space-y-6">
        <!-- Fila: tipo, fecha, serie/folio, comentario -->
        <div class="grid grid-cols-12 gap-4">
            <!-- Tipo -->
            <div class="col-span-12 md:col-span-3">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tipo de comprobante</label>
                <select
                    x-model="form.tipo_comprobante"
                    @change="autofillFolio()"
                    class="form-select w-full"
                >
                    <option value="I">Ingreso (Factura / Honorarios / Arrendamiento)</option>
                    <option value="E">Egreso (Nota de crédito)</option>
                    <option value="T">Traslado</option>
                    <option value="P">Pago</option>
                </select>
            </div>

            <!-- Fecha -->
            <div class="col-span-12 md:col-span-3">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Fecha de emisión</label>
                <input
                    type="datetime-local"
                    x-model="form.fecha"
                    :min="limits.minFecha"
                    :max="limits.maxFecha"
                    class="form-input w-full"
                />
                <div class="text-[11px] text-gray-400 mt-1">Debe estar dentro de las últimas 72 horas.</div>
            </div>

            <!-- Serie -->
            <div class="col-span-6 md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Serie</label>
                <input type="text" x-model="form.serie" class="form-input w-full" />
            </div>

            <!-- Folio -->
            <div class="col-span-6 md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Folio</label>
                <input type="text" x-model="form.folio" class="form-input w-full" />
            </div>

            <!-- Comentario (textarea) -->
            <div class="col-span-12 md:col-span-12">
                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Comentario</label>
                <textarea rows="2" x-model="form.comentario" class="form-textarea w-full" placeholder="Comentario para PDF"></textarea>
            </div>
        </div>

        <!-- Cliente -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Cliente</div>
                <div class="w-72">
                    <select x-model.number="form.cliente_id" @change="onClienteChange"
                            class="form-select w-full">
                        <option value="">— Selecciona un cliente —</option>
                        <template x-for="c in clientes" :key="c.id">
                            <option :value="c.id" x-text="c.razon_social + ' — ' + c.rfc"></option>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Labels compactos, sin “caja dentro de caja” -->
            <template x-if="clienteActual">
                <div class="grid grid-cols-12 gap-2 text-sm">
                    <div class="col-span-12 md:col-span-4">
                        <div class="text-xs uppercase text-gray-400">Razón social</div>
                        <div class="text-gray-800 dark:text-gray-100" x-text="clienteActual.razon_social"></div>
                    </div>
                    <div class="col-span-6 md:col-span-2">
                        <div class="text-xs uppercase text-gray-400">RFC</div>
                        <div class="text-gray-800 dark:text-gray-100" x-text="clienteActual.rfc"></div>
                    </div>
                    <div class="col-span-6 md:col-span-2">
                        <div class="text-xs uppercase text-gray-400">C.P.</div>
                        <div class="text-gray-800 dark:text-gray-100" x-text="clienteActual.codigo_postal"></div>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <div class="text-xs uppercase text-gray-400">Email</div>
                        <div class="text-gray-800 dark:text-gray-100" x-text="clienteActual.email || '—'"></div>
                    </div>

                    <div class="col-span-12 md:col-span-8">
                        <div class="text-xs uppercase text-gray-400">Domicilio</div>
                        <div class="text-gray-800 dark:text-gray-100" x-text="domicilioCliente()"></div>
                    </div>
                    <div class="col-span-12 md:col-span-4 flex items-end md:justify-end">
                        <button type="button" @click="modalCliente=true" class="btn border-gray-200 text-gray-800">
                            Actualizar cliente
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Conceptos -->
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Conceptos</div>
                <button type="button" class="btn bg-gray-900 text-white" @click="addConcepto()">Agregar concepto</button>
            </div>

            <!-- Tabla de conceptos (horizontal, compacto) -->
            <div class="overflow-x-auto">
                <table class="table-auto w-full text-sm">
                    <thead class="text-gray-500">
                        <tr>
                            <th class="px-2 py-2 text-left">Buscar</th>
                            <th class="px-2 py-2 text-left">Descripción</th>
                            <th class="px-2 py-2 text-left">ClaveProdServ</th>
                            <th class="px-2 py-2 text-left">ClaveUnidad</th>
                            <th class="px-2 py-2 text-left">Unidad</th>
                            <th class="px-2 py-2 text-left">Cant.</th>
                            <th class="px-2 py-2 text-left">Precio</th>
                            <th class="px-2 py-2 text-left">Desc.</th>
                            <th class="px-2 py-2 text-left">ObjetoImp</th>
                            <th class="px-2 py-2 text-left">Impuestos</th>
                            <th class="px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in form.conceptos" :key="row.key">
                            <tr class="border-t">
                                <!-- Buscar -->
                                <td class="px-2 py-2 w-48">
                                    <input type="text" class="form-input w-full"
                                           placeholder="Buscar producto…"
                                           x-model="row.buscar"
                                           @input.debounce.400ms="buscarProducto(idx)"
                                           @focus="row.suggestOpen=true"
                                           @blur="setTimeout(()=>row.suggestOpen=false,150)"
                                    >
                                    <!-- Sugerencias -->
                                    <div class="relative" x-show="row.suggestOpen && row.suggests.length">
                                        <div class="absolute z-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md mt-1 w-72 max-h-56 overflow-auto shadow">
                                            <template x-for="p in row.suggests" :key="p.id">
                                                <button type="button" class="block text-left w-full px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                        @mousedown.prevent="aplicarProducto(idx,p)">
                                                    <div class="font-medium text-gray-800 dark:text-gray-100" x-text="p.descripcion"></div>
                                                    <div class="text-xs text-gray-500">
                                                        <span x-text="'CPS: '+(p.clave_prod_serv_id||'')"></span> ·
                                                        <span x-text="'CU: '+(p.clave_unidad_id||'')"></span> ·
                                                        <span x-text="'$'+Number(p.precio||0).toFixed(2)"></span>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                <!-- Descripción -->
                                <td class="px-2 py-2 min-w-[16rem]">
                                    <input type="text" class="form-input w-full" x-model="row.descripcion">
                                </td>

                                <!-- ClaveProdServ -->
                                <td class="px-2 py-2 w-24">
                                    <input type="text" class="form-input w-24" x-model="row.clave_prod_serv_id" maxlength="8">
                                </td>

                                <!-- ClaveUnidad -->
                                <td class="px-2 py-2 w-20">
                                    <input type="text" class="form-input w-20" x-model="row.clave_unidad_id" maxlength="5">
                                </td>

                                <!-- Unidad -->
                                <td class="px-2 py-2 w-24">
                                    <input type="text" class="form-input w-24" x-model="row.unidad" maxlength="10">
                                </td>

                                <!-- Cantidad -->
                                <td class="px-2 py-2 w-20">
                                    <input type="number" step="0.001" class="form-input w-20 text-right" x-model.number="row.cantidad">
                                </td>

                                <!-- Precio -->
                                <td class="px-2 py-2 w-28">
                                    <input type="number" step="0.0001" class="form-input w-28 text-right" x-model.number="row.precio">
                                </td>

                                <!-- Descuento -->
                                <td class="px-2 py-2 w-20">
                                    <input type="number" step="0.01" class="form-input w-20 text-right" x-model.number="row.descuento">
                                </td>

                                <!-- ObjetoImp -->
                                <td class="px-2 py-2 w-40">
                                    <select class="form-select w-40" x-model="row.objeto_imp">
                                        <option value="01">01 - No objeto de impuesto</option>
                                        <option value="02">02 - Sí objeto de impuesto</option>
                                        <option value="03">03 - Sí objeto y no obligado al desglose</option>
                                    </select>
                                </td>

                                <!-- Impuestos -->
                                <td class="px-2 py-2">
                                    <button type="button" class="btn border-gray-200 text-gray-800" @click="row.showTaxes = !row.showTaxes">
                                        Configurar
                                    </button>
                                </td>

                                <!-- Eliminar -->
                                <td class="px-2 py-2 w-8">
                                    <button type="button" class="text-red-500 hover:text-red-600" @click="removeConcepto(idx)">
                                        ✕
                                    </button>
                                </td>
                            </tr>

                            <!-- Fila de impuestos (visible al configurar) -->
                            <tr x-show="row.showTaxes">
                                <td colspan="11" class="px-2 pb-3">
                                    <div class="bg-gray-50 dark:bg-gray-700/30 rounded-md p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <div class="text-xs uppercase text-gray-500">Impuestos del concepto</div>
                                            <div class="space-x-2">
                                                <button type="button" class="btn border-gray-200 text-gray-800" @click="addImpuesto(idx,'trasladado')">+ Trasladado</button>
                                                <button type="button" class="btn border-gray-200 text-gray-800" @click="addImpuesto(idx,'retenido')">+ Retenido</button>
                                            </div>
                                        </div>

                                        <!-- Lista de impuestos -->
                                        <div class="grid grid-cols-12 gap-2">
                                            <template x-for="(imp, iidx) in row.impuestos" :key="iidx">
                                                <div class="col-span-12 md:col-span-6 lg:col-span-4 flex items-end gap-2">
                                                    <div class="w-24">
                                                        <label class="text-[11px] text-gray-500">Tipo</label>
                                                        <select class="form-select w-full" x-model="imp.tipo">
                                                            <option value="trasladado">Trasladado</option>
                                                            <option value="retenido">Retenido</option>
                                                        </select>
                                                    </div>
                                                    <div class="w-28">
                                                        <label class="text-[11px] text-gray-500">Impuesto</label>
                                                        <select class="form-select w-full" x-model="imp.impuesto">
                                                            <option value="001">ISR</option>
                                                            <option value="002">IVA</option>
                                                            <option value="003">IEPS</option>
                                                        </select>
                                                    </div>
                                                    <div class="w-24">
                                                        <label class="text-[11px] text-gray-500">Factor</label>
                                                        <select class="form-select w-full" x-model="imp.factor">
                                                            <option value="Tasa">Tasa</option>
                                                            <option value="Cuota">Cuota</option>
                                                            <option value="Exento">Exento</option>
                                                        </select>
                                                    </div>
                                                    <div class="w-24">
                                                        <label class="text-[11px] text-gray-500">Tasa/Cuota</label>
                                                        <input type="number" step="0.000001" class="form-input w-full text-right" x-model.number="imp.tasa">
                                                    </div>
                                                    <button type="button" class="text-red-500 hover:text-red-600" @click="row.impuestos.splice(iidx,1)">✕</button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" class="btn border-gray-200 text-gray-800" @click="preview()">Vista previa</button>
            <button type="button" class="btn bg-white border-gray-200" @click="guardar('borrador')">Guardar prefactura</button>
            <button type="button" class="btn bg-violet-600 text-white" @click="guardar('timbrar')">Timbrar</button>
        </div>

        <!-- Hidden -->
        <input type="hidden" x-model="form.rfc_usuario_id" />
    </div>

    <!-- Modal: Actualizar Cliente -->
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/30"
        x-show="modalCliente"
        x-transition
        @click.self="modalCliente=false"
        @keydown.escape.window="modalCliente=false"
        x-cloak
    >
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="text-lg font-semibold">Actualizar cliente</div>
                <button class="text-gray-500 hover:text-gray-700" @click="modalCliente=false">✕</button>
            </div>

            <template x-if="clienteEdit">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs text-gray-500">Razón social</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.razon_social">
                    </div>
                    <div class="col-span-6 md:col-span-3">
                        <label class="text-xs text-gray-500">RFC</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.rfc">
                    </div>
                    <div class="col-span-6 md:col-span-3">
                        <label class="text-xs text-gray-500">C.P.</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.codigo_postal">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs text-gray-500">Email</label>
                        <input type="email" class="form-input w-full" x-model="clienteEdit.email">
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs text-gray-500">Calle</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.calle">
                    </div>

                    <div class="col-span-4 md:col-span-2">
                        <label class="text-xs text-gray-500">No. ext</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.no_ext">
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label class="text-xs text-gray-500">No. int</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.no_int">
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <label class="text-xs text-gray-500">Colonia</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.colonia">
                    </div>

                    <div class="col-span-12 md:col-span-3">
                        <label class="text-xs text-gray-500">Localidad</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.localidad">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="text-xs text-gray-500">Estado</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.estado">
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="text-xs text-gray-500">País</label>
                        <input type="text" class="form-input w-full" x-model="clienteEdit.pais">
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-end gap-2 mt-4">
                <button class="btn border-gray-200" @click="modalCliente=false">Cancelar</button>
                <button class="btn bg-violet-600 text-white" @click="guardarCliente()">Guardar cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('facturaForm', (cfg) => ({
        // ---- estado ----
        limits: { minFecha: cfg.minFecha, maxFecha: cfg.maxFecha },
        clientes: cfg.clientes,
        clienteActual: null,
        clienteEdit: null,
        modalCliente: false,

        form: {
            rfc_usuario_id: cfg.rfcUsuarioId,
            tipo_comprobante: 'I',
            fecha: cfg.maxFecha,
            serie: '',
            folio: '',
            comentario: '',
            cliente_id: '',
            conceptos: []
        },

        // ---- init ----
        init() {
            this.addConcepto(); // al menos 1
        },

        // ---- helpers cliente ----
        onClienteChange() {
            const c = this.clientes.find(x => x.id === Number(this.form.cliente_id)) || null;
            this.clienteActual = c;
            this.clienteEdit = c ? JSON.parse(JSON.stringify(c)) : null;
        },
        domicilioCliente() {
            const c = this.clienteActual;
            if (!c) return '—';
            const partes = [
                c.calle,
                c.no_ext ? ('No. ' + c.no_ext) : null,
                c.no_int ? ('Int. ' + c.no_int) : null,
                c.colonia,
                c.localidad,
                c.estado,
                c.codigo_postal ? ('CP ' + c.codigo_postal) : null,
                c.pais
            ].filter(Boolean);
            return partes.join(', ');
        },

        async guardarCliente() {
            if (!this.clienteEdit?.id) return;
            const url = `${cfg.routeClienteUpdateBase}/${this.clienteEdit.id}`;
            const res = await fetch(url, {
                method: 'POST', // algunos setups requieren POST + _method
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                body: JSON.stringify({ ...this.clienteEdit, _method: 'PUT' })
            });
            if (res.ok) {
                // sin recargar: sustituir en cache
                const idx = this.clientes.findIndex(x => x.id === this.clienteEdit.id);
                if (idx >= 0) this.clientes[idx] = JSON.parse(JSON.stringify(this.clienteEdit));
                this.onClienteChange();
                this.modalCliente = false;
            } else {
                alert('No fue posible actualizar el cliente.');
            }
        },

        // ---- serie/folio ----
        async autofillFolio() {
            try {
                const res = await fetch(cfg.apiSeriesNext + '?' + new URLSearchParams({
                    tipo: this.form.tipo_comprobante,
                    rfc_usuario_id: this.form.rfc_usuario_id
                }));
                if (res.ok) {
                    const data = await res.json();
                    this.form.serie = data.serie || '';
                    this.form.folio = data.folio || '';
                }
            } catch (e) { console.error(e); }
        },

        // ---- conceptos ----
        addConcepto() {
            this.form.conceptos.push({
                key: crypto.randomUUID(),
                buscar: '',
                suggests: [],
                suggestOpen: false,
                descripcion: '',
                clave_prod_serv_id: '',
                clave_unidad_id: '',
                unidad: '',
                cantidad: 1,
                precio: 0,
                descuento: 0,
                objeto_imp: '02',
                showTaxes: false,
                impuestos: []
            });
        },
        removeConcepto(idx) {
            this.form.conceptos.splice(idx, 1);
        },
        async buscarProducto(idx) {
            const row = this.form.conceptos[idx];
            const q = (row.buscar || '').trim();
            if (q.length < 2) { row.suggests = []; return; }
            try {
                const res = await fetch(cfg.apiProductosBuscar + '?' + new URLSearchParams({ q }));
                row.suggests = res.ok ? await res.json() : [];
            } catch (e) { row.suggests = []; }
        },
        aplicarProducto(idx, p) {
            const row = this.form.conceptos[idx];
            row.descripcion = p.descripcion || '';
            row.clave_prod_serv_id = p.clave_prod_serv_id || '';
            row.clave_unidad_id = p.clave_unidad_id || '';
            row.unidad = p.unidad || '';
            row.precio = Number(p.precio || 0);
            row.objeto_imp = p.objeto_imp || '02';
            row.suggestOpen = false;
        },

        // ---- impuestos por concepto ----
        addImpuesto(idx, tipo) {
            const row = this.form.conceptos[idx];
            row.impuestos.push({
                tipo,              // 'trasladado' | 'retenido'
                impuesto: '002',   // IVA por defecto
                factor: 'Tasa',    // Tasa/Cuota/Exento
                tasa: 0.160000     // ejemplo 16%
            });
        },

        // ---- acciones ----
        preview() {
            // post a /facturacion/facturas/preview
            this._submitTo('{{ url('/facturacion/facturas/preview') }}');
        },
        guardar(modo) {
            // post a /facturacion/facturas/guardar con modo = borrador|timbrar
            this._submitTo('{{ url('/facturacion/facturas/guardar') }}', { modo });
        },
        _submitTo(url, extra = {}) {
            const payload = { ...this.form, ...extra };
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'payload';
            input.value = JSON.stringify(payload);
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
        },

    }))
});
</script>
