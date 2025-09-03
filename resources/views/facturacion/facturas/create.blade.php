<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>
            {{-- RFC activo arriba a la derecha, solo lectura --}}
            @php
                $rfcActivo = session('rfc_seleccionado') ?? (Auth::user()->rfcs->first()->rfc ?? '—');
            @endphp
            <div class="text-sm text-gray-500 dark:text-gray-400">
                RFC activo: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $rfcActivo }}</span>
            </div>
        </div>
    </x-slot>

    @php
        // Variables que DEBEN venir del controlador FacturaUiController@create
        // $rfcUsuarioId, $clientes, $minFecha, $maxFecha deben existir.
        // Protegemos por si acaso: 
        $rfcUsuarioId = $rfcUsuarioId ?? (Auth::user()->rfcs->first()->id ?? null);
        $clientes = $clientes ?? collect();
        $minFecha = $minFecha ?? now()->subDays(3)->format('Y-m-d\TH:i');
        $maxFecha = $maxFecha ?? now()->format('Y-m-d\TH:i');

        $clientesJson = $clientes->map(function($c){
            return [
                'id'            => (string)$c->id,
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
        class="space-y-6 p-4 md:p-6"
    >

        {{-- ========== Sección: Datos del comprobante (a la par) ========== --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4 md:p-6">
                <div class="grid grid-cols-2 gap-4">
                    {{-- Tipo comprobante --}}
                    <div>
                        <x-label for="tipo" value="Tipo de comprobante" />
                        <select id="tipo" x-model="form.tipo" @change="cargarSerieFolio()"
                                class="form-select w-full">
                            <option value="I">Ingreso</option>
                            <option value="E">Egreso</option>
                            <option value="P">Pago</option>
                            <option value="N">Nómina</option>
                        </select>
                    </div>

                    {{-- Fecha (max: hoy, min: -72h) --}}
                    <div>
                        <x-label for="fecha" value="Fecha" />
                        <input id="fecha" type="datetime-local" class="form-input w-full"
                               :min="minFecha" :max="maxFecha" x-model="form.fecha">
                        <p class="text-xs text-gray-500 mt-1">Solo último 72h, sin futuro.</p>
                    </div>

                    {{-- Serie --}}
                    <div>
                        <x-label for="serie" value="Serie" />
                        <input id="serie" type="text" class="form-input w-full" x-model="form.serie" readonly>
                    </div>
                    {{-- Folio --}}
                    <div>
                        <x-label for="folio" value="Folio" />
                        <input id="folio" type="text" class="form-input w-full" x-model="form.folio" readonly>
                    </div>

                    {{-- Forma de pago (select) --}}
                    <div>
                        <x-label for="forma_pago" value="Forma de pago" />
                        <select id="forma_pago" class="form-select w-full" x-model="form.forma_pago">
                            <option value="">— Selecciona —</option>
                            <option value="01">Efectivo</option>
                            <option value="02">Cheque nominativo</option>
                            <option value="03">Transferencia</option>
                            <option value="04">Tarjeta de crédito</option>
                            <option value="28">Tarjeta de débito</option>
                            <option value="99">Por definir</option>
                        </select>
                    </div>

                    {{-- Método de pago --}}
                    <div>
                        <x-label for="metodo_pago" value="Método de pago" />
                        <select id="metodo_pago" class="form-select w-full" x-model="form.metodo_pago">
                            <option value="PUE">PUE</option>
                            <option value="PPD">PPD</option>
                        </select>
                    </div>

                    {{-- Comentarios (textarea) --}}
                    <div class="col-span-2">
                        <x-label for="comentarios" value="Comentarios (PDF)" />
                        <textarea id="comentarios" class="form-textarea w-full" rows="2" x-model="form.comentarios"></textarea>
                    </div>
                </div>
            </div>

            {{-- ========== Sección: Cliente + slide-over update ========== --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4 md:p-6">
                <div class="flex items-start justify-between mb-3">
                    <x-label value="Cliente" />
                    <button type="button" class="text-violet-600 hover:text-violet-700 text-sm"
                            @click="abrirEditarCliente()">
                        Actualizar cliente
                    </button>
                </div>

                <select class="form-select w-full mb-4" x-model="form.cliente_id" @change="refrescarCliente()">
                    <option value="">— Selecciona un cliente —</option>
                    <template x-for="c in clientes" :key="c.id">
                        <option :value="c.id" x-text="`${c.razon_social} (${c.rfc})`"></option>
                    </template>
                </select>

                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-gray-400">RFC</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.rfc || '—'"></div>
                    </div>
                    <div>
                        <div class="text-gray-400">Razón social</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.razon_social || '—'"></div>
                    </div>
                    <div>
                        <div class="text-gray-400">Calle / No.</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100"
                             x-text="`${clienteSel.calle ?? ''} ${clienteSel.no_ext ?? ''} ${clienteSel.no_int ?? ''}`.trim() || '—'"></div>
                    </div>
                    <div>
                        <div class="text-gray-400">Colonia</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.colonia || '—'"></div>
                    </div>
                    <div>
                        <div class="text-gray-400">Localidad / Estado</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100"
                             x-text="`${clienteSel.localidad ?? ''} ${clienteSel.estado ?? ''}`.trim() || '—'"></div>
                    </div>
                    <div>
                        <div class="text-gray-400">C.P. / País</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100"
                             x-text="`${clienteSel.codigo_postal ?? ''} ${clienteSel.pais ?? ''}`.trim() || '—'"></div>
                    </div>
                    <div class="col-span-2">
                        <div class="text-gray-400">Email</div>
                        <div class="font-medium text-gray-800 dark:text-gray-100" x-text="clienteSel.email || '—'"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========== Sección: Conceptos ========== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4 md:p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Conceptos</h3>
                <button type="button" class="btn bg-violet-500 hover:bg-violet-600 text-white"
                        @click="agregarConcepto()">Agregar concepto</button>
            </div>

            {{-- Buscador arriba (no se esconde) --}}
            <div class="mb-3">
                <label class="text-sm text-gray-600 dark:text-gray-300">Buscar producto</label>
                <input type="text" class="form-input w-full" placeholder="Escribe 3+ caracteres…"
                       x-model="busquedaProducto"
                       @input.debounce.300ms="buscarProductos()">
                <div class="mt-2 max-h-48 overflow-auto border border-gray-200 dark:border-gray-700/60 rounded-lg"
                     x-show="sugerencias.length">
                    <template x-for="p in sugerencias" :key="p.id">
                        <button type="button" class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                                @click="elegirProducto(p)">
                            <div class="font-medium text-gray-800 dark:text-gray-100" x-text="p.descripcion"></div>
                            <div class="text-xs text-gray-500" x-text="`$ ${Number(p.precio||0).toFixed(2)} · ${p.clave_prod_serv} / ${p.clave_unidad}`"></div>
                        </button>
                    </template>
                </div>
            </div>

            {{-- Tabla compacta --}}
            <div class="overflow-x-auto">
                <table class="table-auto w-full text-sm">
                    <thead class="text-gray-500">
                        <tr>
                            <th class="px-2 py-2 text-left">Cant</th>
                            <th class="px-2 py-2 text-left">Clave ProdServ</th>
                            <th class="px-2 py-2 text-left">Clave Unidad</th>
                            <th class="px-2 py-2 text-left">Descripción</th>
                            <th class="px-2 py-2 text-left">P. Unit</th>
                            <th class="px-2 py-2 text-left">ObjImp</th>
                            <th class="px-2 py-2 text-left">Impuestos</th>
                            <th class="px-2 py-2 text-right">Importe</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(c, idx) in form.conceptos" :key="c.uid">
                            <tr class="border-t border-gray-200 dark:border-gray-700/60">
                                <td class="px-2 py-2">
                                    <input type="number" min="0" step="0.001" class="form-input w-20"
                                           x-model.number="c.cantidad" @input="recalcular(idx)">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" class="form-input w-28" x-model="c.clave_prod_serv">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" class="form-input w-24" x-model="c.clave_unidad">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" class="form-input w-64" x-model="c.descripcion">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number" min="0" step="0.01" class="form-input w-28"
                                           x-model.number="c.precio_unit" @input="recalcular(idx)">
                                </td>
                                <td class="px-2 py-2">
                                    <input type="text" class="form-input w-20" x-model="c.objeto_imp" placeholder="01/02/03">
                                </td>
                                <td class="px-2 py-2">
                                    <button type="button" class="btn bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200"
                                            @click="abrirImpuestos(idx)">Configurar</button>
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <span x-text="formatMoney(c.importe)"></span>
                                </td>
                                <td class="px-2 py-2">
                                    <button type="button" class="text-red-500 hover:text-red-600" @click="quitarConcepto(idx)">✕</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-end gap-6 text-sm">
                <div class="text-gray-500">Subtotal: <span class="font-semibold text-gray-800 dark:text-gray-100" x-text="formatMoney(totales.subtotal)"></span></div>
                <div class="text-gray-500">Impuestos: <span class="font-semibold text-gray-800 dark:text-gray-100" x-text="formatMoney(totales.impuestos)"></span></div>
                <div class="text-gray-700 dark:text-gray-100 text-base">Total: <span class="font-bold" x-text="formatMoney(totales.total)"></span></div>
            </div>
        </div>

        {{-- ========== Sección: Documentos relacionados (múltiples) ========== --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xs p-4 md:p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">Documentos relacionados</h3>
                <button type="button" class="btn bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
                        @click="agregarRelacion()">Agregar</button>
            </div>
            <div class="space-y-3">
                <template x-for="(r, i) in form.relaciones" :key="r.uid">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div>
                            <x-label value="Tipo rel." />
                            <select class="form-select w-full" x-model="r.tipo">
                                <option value="01">01 - Nota de crédito</option>
                                <option value="04">04 - Sustitución</option>
                                <option value="07">07 - CFDI por aplicación de anticipo</option>
                                <!-- agrega las que necesites -->
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <x-label value="UUID" />
                            <input type="text" class="form-input w-full" x-model="r.uuid" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX">
                        </div>
                        <div class="md:col-span-4">
                            <button type="button" class="text-red-500 hover:text-red-600 text-sm" @click="quitarRelacion(i)">Quitar</button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ========== Acciones ========== --}}
        <div class="flex items-center justify-end gap-3">
            <button type="button" class="btn border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200"
                    @click="visualizar()">Visualizar</button>
            <button type="button" class="btn bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
                    @click="guardar('borrador')">Guardar como prefactura</button>
            <button type="button" class="btn bg-violet-500 hover:bg-violet-600 text-white"
                    @click="guardar('timbrar')">Timbrar</button>
        </div>

        {{-- ========== Slide-over: Editar cliente ========== --}}
        <div x-show="ui.editarCliente" x-transition
             class="fixed inset-0 z-50 flex">
            <div class="w-full h-full bg-black/40" @click="ui.editarCliente=false"></div>
            <div class="ml-auto h-full w-full max-w-md bg-white dark:bg-gray-800 shadow-xl p-6 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Actualizar cliente</h4>
                    <button class="text-gray-500 hover:text-gray-700" @click="ui.editarCliente=false">✕</button>
                </div>

                <template x-if="form.cliente_id">
                    <form @submit.prevent="submitActualizarCliente">
                        <div class="space-y-3">
                            <div>
                                <x-label value="Razón social" />
                                <input type="text" class="form-input w-full" x-model="clienteEdit.razon_social">
                            </div>
                            <div>
                                <x-label value="RFC" />
                                <input type="text" class="form-input w-full" x-model="clienteEdit.rfc">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <x-label value="Calle" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.calle">
                                </div>
                                <div>
                                    <x-label value="No. exterior" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.no_ext">
                                </div>
                                <div>
                                    <x-label value="No. interior" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.no_int">
                                </div>
                                <div>
                                    <x-label value="Colonia" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.colonia">
                                </div>
                                <div>
                                    <x-label value="Localidad" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.localidad">
                                </div>
                                <div>
                                    <x-label value="Estado" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.estado">
                                </div>
                                <div>
                                    <x-label value="C.P." />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.codigo_postal">
                                </div>
                                <div>
                                    <x-label value="País" />
                                    <input type="text" class="form-input w-full" x-model="clienteEdit.pais">
                                </div>
                            </div>
                            <div>
                                <x-label value="Email" />
                                <input type="email" class="form-input w-full" x-model="clienteEdit.email">
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" class="btn border border-gray-200 dark:border-gray-700"
                                    @click="ui.editarCliente=false">Cancelar</button>
                            <button type="submit" class="btn bg-violet-500 hover:bg-violet-600 text-white">
                                Guardar
                            </button>
                        </div>
                    </form>
                </template>

                <template x-if="!form.cliente_id">
                    <div class="text-sm text-gray-500">
                        Selecciona primero un cliente para editar.
                    </div>
                </template>
            </div>
        </div>

        {{-- ========== Mini-modal: Impuestos por concepto ========== --}}
        <div x-show="ui.impuestos.visible" x-transition
             class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="cerrarImpuestos()"></div>
            <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl p-6 w-full max-w-md">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Impuestos del concepto</h4>
                    <button class="text-gray-500 hover:text-gray-700" @click="cerrarImpuestos()">✕</button>
                </div>

                <div class="space-y-3">
                    <div class="text-sm text-gray-500">Traslados</div>
                    <template x-for="(t, ti) in ui.impuestos.data.traslados" :key="`tr-${ti}`">
                        <div class="grid grid-cols-3 gap-2">
                            <select class="form-select" x-model="t.impuesto">
                                <option value="002">IVA</option>
                                <option value="001">ISR</option>
                                <option value="003">IEPS</option>
                            </select>
                            <select class="form-select" x-model="t.tipo_factor">
                                <option value="Tasa">Tasa</option>
                                <option value="Cuota">Cuota</option>
                                <option value="Exento">Exento</option>
                            </select>
                            <input type="number" step="0.0001" class="form-input" x-model.number="t.tasa_cuota" placeholder="0.1600">
                        </div>
                    </template>
                    <button type="button" class="text-violet-600 text-sm" @click="addTraslado()">+ Agregar traslado</button>

                    <div class="text-sm text-gray-500 mt-4">Retenciones</div>
                    <template x-for="(r, ri) in ui.impuestos.data.retenciones" :key="`re-${ri}`">
                        <div class="grid grid-cols-3 gap-2">
                            <select class="form-select" x-model="r.impuesto">
                                <option value="002">IVA</option>
                                <option value="001">ISR</option>
                                <option value="003">IEPS</option>
                            </select>
                            <select class="form-select" x-model="r.tipo_factor">
                                <option value="Tasa">Tasa</option>
                                <option value="Cuota">Cuota</option>
                            </select>
                            <input type="number" step="0.0001" class="form-input" x-model.number="r.tasa_cuota" placeholder="0.1067">
                        </div>
                    </template>
                    <button type="button" class="text-violet-600 text-sm" @click="addRetencion()">+ Agregar retención</button>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button class="btn border border-gray-200 dark:border-gray-700" @click="cerrarImpuestos()">Cancelar</button>
                    <button class="btn bg-violet-500 hover:bg-violet-600 text-white" @click="guardarImpuestos()">Guardar</button>
                </div>
            </div>
        </div>

    </div>

    {{-- ========== JS Inline: define window.facturaForm (evita “facturaForm is not defined”) ========== --}}
    <script>
    window.facturaForm = (opts) => ({
        // ----- props -----
        rfcUsuarioId: opts.rfcUsuarioId,
        clientes: opts.clientes || [],
        minFecha: opts.minFecha,
        maxFecha: opts.maxFecha,
        apiSeriesNext: opts.apiSeriesNext,
        apiProductosBuscar: opts.apiProductosBuscar,
        routeClienteUpdateBase: opts.routeClienteUpdateBase,
        csrf: opts.csrf,

        // ----- estado -----
        form: {
            tipo: 'I',
            fecha: opts.maxFecha,
            serie: '',
            folio: '',
            forma_pago: '',
            metodo_pago: 'PUE',
            comentarios: '',
            cliente_id: '',
            conceptos: [],
            relaciones: [],
        },

        clienteSel: {},
        clienteEdit: {},
        ui: {
            editarCliente: false,
            impuestos: { visible: false, index: null, data: { traslados: [], retenciones: [] } },
        },

        // productos
        busquedaProducto: '',
        sugerencias: [],
        totales: { subtotal: 0, impuestos: 0, total: 0 },

        // ----- lifecycle -----
        async init() {
            // default serie/folio para tipo ingreso
            await this.cargarSerieFolio();
        },

        // ----- helpers -----
        formatMoney(n) {
            const x = Number(n || 0);
            return x.toLocaleString('es-MX', { style: 'currency', currency: 'MXN' });
        },

        // ----- Serie / Folio -----
        async cargarSerieFolio() {
            try {
                const params = new URLSearchParams({ tipo: this.form.tipo, rfc_usuario_id: this.rfcUsuarioId });
                const res = await fetch(`${this.apiSeriesNext}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.form.serie = data.serie || '';
                this.form.folio = data.folio || '';
            } catch (e) {
                console.error('Error cargando Serie/Folio', e);
                this.form.serie = '';
                this.form.folio = '';
            }
        },

        // ----- Cliente -----
        refrescarCliente() {
            const c = this.clientes.find(x => String(x.id) === String(this.form.cliente_id));
            this.clienteSel = c || {};
            this.clienteEdit = JSON.parse(JSON.stringify(this.clienteSel || {}));
        },
        abrirEditarCliente() {
            if (!this.form.cliente_id) return;
            this.ui.editarCliente = true;
        },
        async submitActualizarCliente() {
            try {
                const id = this.form.cliente_id;
                const res = await fetch(`${this.routeClienteUpdateBase}/${id}`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: new URLSearchParams({ _method: 'PUT', ...this.clienteEdit })
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                // sin recargar: actualizamos en memoria
                const idx = this.clientes.findIndex(x => String(x.id) === String(id));
                if (idx >= 0) this.clientes[idx] = JSON.parse(JSON.stringify(this.clienteEdit));
                this.refrescarCliente();
                this.ui.editarCliente = false;
            } catch (e) {
                console.error('Error actualizando cliente', e);
                alert('No se pudo actualizar el cliente.');
            }
        },

        // ----- Conceptos -----
        agregarConcepto() {
            this.form.conceptos.push({
                uid: crypto.randomUUID(),
                cantidad: 1,
                clave_prod_serv: '',
                clave_unidad: '',
                descripcion: '',
                precio_unit: 0,
                objeto_imp: '',
                importe: 0,
                impuestos: { traslados: [], retenciones: [] },
            });
            this.recalcular();
        },
        quitarConcepto(i) {
            this.form.conceptos.splice(i, 1);
            this.recalcular();
        },
        recalcular() {
            let subtotal = 0, impuestos = 0;
            this.form.conceptos.forEach(c => {
                const imp = (Number(c.cantidad||0) * Number(c.precio_unit||0));
                c.importe = imp;
                subtotal += imp;

                // calcula impuestos básicos (solo como referencia visual)
                const base = imp;
                (c.impuestos.traslados || []).forEach(t => {
                    const tasa = Number(t.tasa_cuota||0);
                    if (t.tipo_factor === 'Tasa') impuestos += base * tasa;
                });
                (c.impuestos.retenciones || []).forEach(r => {
                    const tasa = Number(r.tasa_cuota||0);
                    if (r.tipo_factor === 'Tasa') impuestos -= base * tasa;
                });
            });
            this.totales.subtotal = subtotal;
            this.totales.impuestos = impuestos;
            this.totales.total = subtotal + impuestos;
        },

        // Buscador de productos
        async buscarProductos() {
            const q = (this.busquedaProducto || '').trim();
            if (q.length < 3) { this.sugerencias = []; return; }
            try {
                const params = new URLSearchParams({ q, rfc_usuario_id: this.rfcUsuarioId });
                const res = await fetch(`${this.apiProductosBuscar}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                this.sugerencias = Array.isArray(data) ? data : [];
            } catch (e) {
                console.error('Buscar productos:', e);
                this.sugerencias = [];
            }
        },
        elegirProducto(p) {
            // Inserta/llena el último concepto o crea uno
            let c = this.form.conceptos[this.form.conceptos.length - 1];
            if (!c) { this.agregarConcepto(); c = this.form.conceptos[this.form.conceptos.length - 1]; }
            c.descripcion     = p.descripcion || '';
            c.precio_unit     = Number(p.precio || 0);
            c.clave_prod_serv = p.clave_prod_serv || '';
            c.clave_unidad    = p.clave_unidad || '';
            c.objeto_imp      = p.objeto_imp || '';
            this.recalcular();
            this.busquedaProducto = '';
            this.sugerencias = [];
        },

        // Impuestos por concepto
        abrirImpuestos(idx) {
            this.ui.impuestos.index = idx;
            const c = this.form.conceptos[idx];
            // clonar
            this.ui.impuestos.data = JSON.parse(JSON.stringify(c.impuestos || { traslados: [], retenciones: [] }));
            this.ui.impuestos.visible = true;
        },
        cerrarImpuestos() {
            this.ui.impuestos.visible = false;
            this.ui.impuestos.index = null;
        },
        addTraslado() {
            this.ui.impuestos.data.traslados.push({ impuesto: '002', tipo_factor: 'Tasa', tasa_cuota: 0.16 });
        },
        addRetencion() {
            this.ui.impuestos.data.retenciones.push({ impuesto: '002', tipo_factor: 'Tasa', tasa_cuota: 0.1067 });
        },
        guardarImpuestos() {
            const i = this.ui.impuestos.index;
            if (i == null) return;
            this.form.conceptos[i].impuestos = JSON.parse(JSON.stringify(this.ui.impuestos.data));
            this.cerrarImpuestos();
            this.recalcular();
        },

        // Relaciones
        agregarRelacion() { this.form.relaciones.push({ uid: crypto.randomUUID(), tipo: '01', uuid: '' }); },
        quitarRelacion(i) { this.form.relaciones.splice(i,1); },

        // Acciones
        visualizar() {
            // POST a /facturacion/facturas/preview (si ya está tu ruta)
            alert('Vista previa: aquí mandarías los datos al endpoint de preview.');
        },
        guardar(modo) {
            // POST a /facturacion/facturas/guardar
            alert('Guardar: ' + modo);
        },
    });
    </script>
</x-app-layout>
