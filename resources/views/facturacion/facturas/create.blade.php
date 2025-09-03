{{-- resources/views/facturacion/facturas/create.blade.php --}}
<x-app-layout>
    @php
        // -------- Datos que espera la vista --------
        // Controlador debe enviar: $rfcUsuarioId, $clientes (Collection), $minFecha (Y-m-d\TH:i), $maxFecha (Y-m-d\TH:i)
        // Fallbacks seguros por si faltara algo:
        $rfcUsuarioId = $rfcUsuarioId ?? (int) (session('rfc_usuario_id') ?? session('rfc_activo_id') ?? 0);
        $minFecha     = $minFecha     ?? now()->subHours(72)->format('Y-m-d\TH:i');
        $maxFecha     = $maxFecha     ?? now()->format('Y-m-d\TH:i');
        $clientes     = $clientes     ?? collect();

        $clientesJson = $clientes->map(function ($c) {
            return [
                'id'             => (string) $c->id,
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
        })->values()->toJson(JSON_UNESCAPED_UNICODE);

        // Mostrar emisor (RFC activo) en header derecho
        $rfcActivo     = session('rfc_seleccionado') ?? session('rfc_activo') ?? session('rfc') ?? 'RFC no definido';
        $razonEmisor   = session('razon_social') ?? '—';
    @endphp

    {{-- Alpine component ONLY: no re-registramos plugins (evita error "$persist") --}}
    <script type="module">
        window.facturaForm = (opts) => ({
            // ================== STATE ==================
            ready: false,
            csrf: opts.csrf,
            rfcUsuarioId: Number(opts.rfcUsuarioId || 0),
            minFecha: opts.minFecha,
            maxFecha: opts.maxFecha,
            apiSeriesNext: opts.apiSeriesNext,
            apiProductosBuscar: opts.apiProductosBuscar,
            routeClienteUpdateBase: opts.routeClienteUpdateBase,
            clientes: opts.clientes || [],
            // Cabecera
            form: {
                tipo_comprobante: 'I',     // default Ingreso
                serie: '',
                folio: '',
                fecha: opts.maxFecha,      // hoy (limitado a 72h atrás)
                forma_pago: '',            // select
                metodo_pago: 'PUE',        // usual por omisión
                moneda: 'MXN',
                uso_cfdi: '',             // (si lo ocupas después)
                cliente_id: '',
                comentarios_pdf: '',
            },
            // Cliente seleccionado (vista label)
            clienteView: null,

            // ================== CONCEPTOS ==================
            productosSearching: false,
            productosQuery: '',
            productosResults: [],
            showResults: false,

            conceptos: [],  // {descripcion,cantidad,precio,descuento,clave_prod_serv_id,clave_unidad_id,unidad,impuestos:[]}
            // impuesto: {tipo: 'trasladado'|'retencion', impuesto:'001|002|003', tasa: 0.160000, base_modo:'monto'|'porc', valor: 0}

            // ================== DOCUMENTOS RELACIONADOS ==================
            relaciones: [], // {tipo: '01','uuid':'...'}
            relacionTipo: '01',
            relacionUUID: '',

            // ================== IMPUESTOS GLOBALES ==================
            globales: [], // mismo formato que impuestos de concepto

            // ================== MODAL lateral cliente ==================
            showClienteDrawer: false,
            clienteEdit: {
                id: '',
                rfc: '',
                razon_social: '',
                calle: '',
                no_ext: '',
                no_int: '',
                colonia: '',
                localidad: '',
                estado: '',
                codigo_postal: '',
                pais: '',
                email: '',
            },

            // ================== INIT ==================
            async init() {
                try {
                    // Preseleccionar primer cliente si hay
                    if (this.clientes.length > 0) {
                        this.form.cliente_id = this.clientes[0].id;
                        this.onClienteChange();
                    }
                    // Cargar Serie/Folio de "I" al abrir
                    await this.fetchSerieFolio();
                } catch (e) {
                    console.error(e);
                } finally {
                    this.ready = true;
                }
            },

            // ================== SERIES / FOLIOS ==================
            async fetchSerieFolio() {
                try {
                    const tipo = this.form.tipo_comprobante || 'I';
                    const url  = `${this.apiSeriesNext}?tipo=${encodeURIComponent(tipo)}`;
                    const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    this.form.serie = data.serie ?? '';
                    this.form.folio = data.folio ?? '';
                } catch (e) {
                    console.error('Error obteniendo serie/folio', e);
                }
            },
            async onTipoChange() {
                await this.fetchSerieFolio();
            },

            // ================== CLIENTE ==================
            onClienteChange() {
                const c = this.clientes.find(x => String(x.id) === String(this.form.cliente_id));
                this.clienteView = c || null;
            },
            abrirDrawerCliente() {
                if (!this.clienteView) return;
                Object.assign(this.clienteEdit, this.clienteView);
                this.showClienteDrawer = true;
            },
            cerrarDrawerCliente() {
                this.showClienteDrawer = false;
            },
            async guardarCliente() {
                try {
                    const id  = this.clienteEdit.id;
                    const url = `${this.routeClienteUpdateBase}/${id}`;
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrf,
                            'X-HTTP-Method-Override': 'PUT',
                            'Accept': 'application/json',
                        },
                        body: new URLSearchParams(this.clienteEdit),
                    });
                    if (!res.ok) {
                        console.error('Error actualizando cliente', await res.text());
                        alert('No se pudo actualizar el cliente.');
                        return;
                    }
                    // Actualiza en memoria
                    const idx = this.clientes.findIndex(x => String(x.id) === String(id));
                    if (idx >= 0) this.clientes[idx] = { ...this.clienteEdit };
                    this.onClienteChange();
                    this.cerrarDrawerCliente();
                } catch (e) {
                    console.error(e);
                    alert('Error inesperado al actualizar cliente.');
                }
            },

            // ================== PRODUCTOS ==================
            async buscarProductos() {
                this.productosSearching = true;
                try {
                    const q = this.productosQuery.trim();
                    if (!q) {
                        this.productosResults = [];
                        this.showResults = false;
                        return;
                    }
                    const url = `${this.apiProductosBuscar}?q=${encodeURIComponent(q)}&limit=10`;
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    // Esperamos objetos con: id, descripcion, precio, clave_prod_serv_id, clave_unidad_id, unidad
                    this.productosResults = Array.isArray(data) ? data : [];
                    this.showResults = true;
                } catch (e) {
                    console.error(e);
                } finally {
                    this.productosSearching = false;
                }
            },
            seleccionarProducto(p) {
                // Agrega renglón precargado y cierra resultados
                this.conceptos.push({
                    descripcion: p.descripcion || '',
                    cantidad: 1,
                    precio: Number(p.precio || 0),
                    descuento: 0,
                    clave_prod_serv_id: p.clave_prod_serv_id || '',
                    clave_unidad_id: p.clave_unidad_id || '',
                    unidad: p.unidad || '',
                    impuestos: [], // se agregan con modal
                });
                this.productosQuery = '';
                this.productosResults = [];
                this.showResults = false;
            },
            eliminarConcepto(i) {
                this.conceptos.splice(i, 1);
            },

            // ================== IMPUESTOS POR CONCEPTO ==================
            abrirModalImpuestoIdx: null,
            nuevoImp: { tipo:'trasladado', impuesto:'002', tasa:'0.160000', base_modo:'porc', valor:'0' },

            abrirImpuesto(i) {
                this.abrirModalImpuestoIdx = i;
                // defaults
                this.nuevoImp = { tipo:'trasladado', impuesto:'002', tasa:'0.160000', base_modo:'porc', valor:'0' };
            },
            cerrarImpuesto() {
                this.abrirModalImpuestoIdx = null;
            },
            agregarImpuesto() {
                const i = this.abrirModalImpuestoIdx;
                if (i === null || i === undefined) return;
                this.conceptos[i].impuestos.push({ ...this.nuevoImp });
                this.cerrarImpuesto();
            },
            eliminarImpuesto(i, j) {
                this.conceptos[i].impuestos.splice(j, 1);
            },

            // ================== RELACIONES ==================
            agregarRelacion() {
                const uuid = (this.relacionUUID || '').trim().toUpperCase();
                if (!uuid) return;
                this.relaciones.push({ tipo: this.relacionTipo, uuid });
                this.relacionUUID = '';
            },
            borrarRelacion(idx) {
                this.relaciones.splice(idx, 1);
            },

            // ================== IMPUESTOS GLOBALES ==================
            agregarGlobal() {
                this.globales.push({ tipo:'trasladado', impuesto:'002', tasa:'0.160000', base_modo:'porc', valor:'0' });
            },
            borrarGlobal(i) {
                this.globales.splice(i, 1);
            },

            // ================== TOTALIZADORES (básicos, sólo vista) ==================
            lineaImporte(c) {
                const subtotal = (Number(c.cantidad || 0) * Number(c.precio || 0)) - Number(c.descuento || 0);
                return Math.max(subtotal, 0);
            },
            totalSubtotal() {
                return this.conceptos.reduce((acc, c) => acc + this.lineaImporte(c), 0);
            },
            totalImpuestosTras() {
                // Simple: suma concepto * tasa (solo trasladados)
                let t = 0;
                this.conceptos.forEach(c => {
                    const base = this.lineaImporte(c);
                    c.impuestos.filter(x => x.tipo === 'trasladado').forEach(x => {
                        let imp = 0;
                        if (String(x.base_modo) === 'porc') {
                            imp = base * Number(x.tasa || 0);
                        } else {
                            imp = Number(x.valor || 0);
                        }
                        t += imp;
                    });
                });
                // Globales
                this.globales.filter(x => x.tipo === 'trasladado').forEach(x => {
                    // base global = subtotal
                    const base = this.totalSubtotal();
                    let imp = 0;
                    if (String(x.base_modo) === 'porc') {
                        imp = base * Number(x.tasa || 0);
                    } else {
                        imp = Number(x.valor || 0);
                    }
                    t += imp;
                });
                return t;
            },
            totalImpuestosRet() {
                let t = 0;
                this.conceptos.forEach(c => {
                    const base = this.lineaImporte(c);
                    c.impuestos.filter(x => x.tipo === 'retencion').forEach(x => {
                        let imp = 0;
                        if (String(x.base_modo) === 'porc') {
                            imp = base * Number(x.tasa || 0);
                        } else {
                            imp = Number(x.valor || 0);
                        }
                        t += imp;
                    });
                });
                // Globales
                this.globales.filter(x => x.tipo === 'retencion').forEach(x => {
                    const base = this.totalSubtotal();
                    let imp = 0;
                    if (String(x.base_modo) === 'porc') {
                        imp = base * Number(x.tasa || 0);
                    } else {
                        imp = Number(x.valor || 0);
                    }
                    t += imp;
                });
                return t;
            },
            totalGeneral() {
                return this.totalSubtotal() + this.totalImpuestosTras() - this.totalImpuestosRet();
            },

            // ================== SUBMIT (preview / guardar / timbrar) ==================
            async submitPreview() {
                // arma payload simple (el backend validará)
                const form = document.getElementById('factura-form');
                form.action = "{{ url('/facturacion/facturas/preview') }}";
                form.target = "_blank";
                form.submit();
            },
            submitGuardar() {
                const form = document.getElementById('factura-form');
                form.action = "{{ url('/facturacion/facturas/guardar') }}";
                form.target = "";
                form.submit();
            },
            submitTimbrar() {
                // si vas a timbrar directo, puedes usar un parámetro ?timbrar=1 o endpoint distinto
                const form = document.getElementById('factura-form');
                form.action = "{{ url('/facturacion/facturas/guardar') }}?timbrar=1";
                form.target = "";
                form.submit();
            },
        });
    </script>

    <div
        x-data="facturaForm({
            rfcUsuarioId: {{ (int) $rfcUsuarioId }},
            clientes: {!! $clientesJson !!},
            minFecha: '{{ $minFecha }}',
            maxFecha: '{{ $maxFecha }}',
            apiSeriesNext: '{{ url('/api/series/next') }}',
            apiProductosBuscar: '{{ url('/api/productos/buscar') }}',
            routeClienteUpdateBase: '{{ url('/catalogos/clientes') }}',
            csrf: '{{ csrf_token() }}'
        })"
        x-init="init()"
        class="p-4 sm:p-6 space-y-6"
    >
        {{-- Título + Emisor (arriba derecha) --}}
        <div class="flex items-start justify-between">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>
            <div class="text-right">
                <div class="text-xs uppercase text-gray-400 dark:text-gray-500">Emisor (RFC activo)</div>
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $rfcActivo }}</div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400">{{ $razonEmisor }}</div>
            </div>
        </div>

        {{-- FORM MAIN --}}
        <form id="factura-form" method="POST" class="space-y-6">
            @csrf
            {{-- ========== Sección 1: Datos del comprobante ========== --}}
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                <div class="grid grid-cols-12 gap-4">
                    {{-- Tipo de comprobante --}}
                    <div class="col-span-12 sm:col-span-2">
                        <x-label class="mb-1">Tipo de comprobante</x-label>
                        <select class="form-select w-full" x-model="form.tipo_comprobante" @change="onTipoChange()">
                            <option value="I">Ingreso</option>
                            <option value="E">Egreso</option>
                            <option value="P">Pago</option>
                            <option value="N">Nómina</option>
                            <option value="T">Traslado</option>
                        </select>
                    </div>

                    {{-- Serie --}}
                    <div class="col-span-6 sm:col-span-2">
                        <x-label class="mb-1">Serie</x-label>
                        <input type="text" class="form-input w-full" x-model="form.serie" readonly>
                    </div>

                    {{-- Folio --}}
                    <div class="col-span-6 sm:col-span-2">
                        <x-label class="mb-1">Folio</x-label>
                        <input type="text" class="form-input w-full" x-model="form.folio" readonly>
                    </div>

                    {{-- Fecha (limitada 72h atrás a ahora) --}}
                    <div class="col-span-6 sm:col-span-3">
                        <x-label class="mb-1">Fecha</x-label>
                        <input type="datetime-local" class="form-input w-full"
                               x-model="form.fecha"
                               :min="minFecha"
                               :max="maxFecha">
                        <div class="text-[11px] text-gray-500 mt-1">Permitido: desde {{ \Carbon\Carbon::parse($minFecha)->format('d/m/Y H:i') }} hasta {{ \Carbon\Carbon::parse($maxFecha)->format('d/m/Y H:i') }}</div>
                    </div>

                    {{-- Forma de Pago (select) --}}
                    <div class="col-span-6 sm:col-span-3">
                        <x-label class="mb-1">Forma de pago</x-label>
                        <select class="form-select w-full" x-model="form.forma_pago">
                            <option value="">— Selecciona —</option>
                            <option value="01">01 - Efectivo</option>
                            <option value="02">02 - Cheque nominativo</option>
                            <option value="03">03 - Transferencia electrónica</option>
                            <option value="04">04 - Tarjeta de crédito</option>
                            <option value="28">28 - Tarjeta de débito</option>
                            <option value="99">99 - Por definir</option>
                        </select>
                    </div>

                    {{-- Método de pago --}}
                    <div class="col-span-6 sm:col-span-3">
                        <x-label class="mb-1">Método de pago</x-label>
                        <select class="form-select w-full" x-model="form.metodo_pago">
                            <option value="PUE">PUE - Pago en una sola exhibición</option>
                            <option value="PPD">PPD - Pago en parcialidades o diferido</option>
                        </select>
                    </div>

                    {{-- Moneda --}}
                    <div class="col-span-6 sm:col-span-2">
                        <x-label class="mb-1">Moneda</x-label>
                        <select class="form-select w-full" x-model="form.moneda">
                            <option value="MXN">MXN - Peso Mexicano</option>
                            <option value="USD">USD - Dólar</option>
                        </select>
                    </div>

                    {{-- Comentarios PDF (textarea) --}}
                    <div class="col-span-12">
                        <x-label class="mb-1">Comentarios (PDF)</x-label>
                        <textarea class="form-textarea w-full" rows="2" x-model="form.comentarios_pdf" placeholder="Opcional"></textarea>
                    </div>
                </div>
            </div>

            {{-- ========== Sección 2: Cliente ========== --}}
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 sm:col-span-6">
                        <x-label class="mb-1">Cliente</x-label>
                        <select class="form-select w-full" x-model="form.cliente_id" @change="onClienteChange()">
                            <template x-for="c in clientes" :key="c.id">
                                <option :value="c.id" x-text="`${c.razon_social} (${c.rfc})`"></option>
                            </template>
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-6 flex items-end justify-end">
                        <button type="button" class="btn-sm bg-violet-500 hover:bg-violet-600 text-white rounded-lg"
                                @click="abrirDrawerCliente()">
                            Actualizar cliente
                        </button>
                    </div>

                    {{-- Labels con datos (sin recuadro extra) --}}
                    <template x-if="clienteView">
                        <div class="col-span-12 grid grid-cols-12 gap-4">
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs text-gray-500">Razón social</div>
                                <div class="text-sm font-medium" x-text="clienteView.razon_social"></div>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <div class="text-xs text-gray-500">RFC</div>
                                <div class="text-sm font-medium" x-text="clienteView.rfc"></div>
                            </div>
                            <div class="col-span-6 sm:col-span-3">
                                <div class="text-xs text-gray-500">CP</div>
                                <div class="text-sm font-medium" x-text="clienteView.codigo_postal"></div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs text-gray-500">Dirección</div>
                                <div class="text-sm font-medium" x-text="`${clienteView.calle ?? ''} ${clienteView.no_ext ?? ''}${clienteView.no_int ? (' Int. '+clienteView.no_int) : ''}, ${clienteView.colonia ?? ''}`"></div>
                                <div class="text-sm" x-text="`${clienteView.localidad ?? ''}, ${clienteView.estado ?? ''}, ${clienteView.pais ?? ''}`"></div>
                            </div>
                            <div class="col-span-12 sm:col-span-6">
                                <div class="text-xs text-gray-500">Correo</div>
                                <div class="text-sm font-medium" x-text="clienteView.email"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ========== Sección 3: Conceptos (con buscador fijo arriba) ========== --}}
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                {{-- Header sticky con buscador --}}
                <div class="sticky top-0 bg-white/80 dark:bg-gray-800/80 backdrop-blur rounded-t-xl -m-4 sm:-m-6 p-4 sm:p-6 pb-4 border-b border-gray-200 dark:border-gray-700/60">
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-12 lg:col-span-8">
                            <div class="text-xs uppercase text-gray-400">Agregar producto / servicio</div>
                            <div class="relative mt-1">
                                <input type="text" class="form-input w-full" placeholder="Buscar por descripción o clave"
                                       x-model.debounce.300ms="productosQuery" @input="buscarProductos()"
                                       @focus="showResults = productosResults.length > 0" @blur="setTimeout(()=>showResults=false,150)">
                                {{-- Resultados --}}
                                <div class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 rounded-lg shadow-lg"
                                     x-show="showResults">
                                    <template x-if="productosResults.length === 0 && productosQuery">
                                        <div class="p-3 text-sm text-gray-500">Sin resultados…</div>
                                    </template>
                                    <template x-for="p in productosResults" :key="p.id">
                                        <button type="button"
                                                class="w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded"
                                                @mousedown.prevent="seleccionarProducto(p)">
                                            <div class="text-sm font-medium" x-text="p.descripcion"></div>
                                            <div class="text-[11px] text-gray-500">
                                                <span class="mr-2">Clave ProdServ: <span x-text="p.clave_prod_serv_id"></span></span>
                                                <span class="mr-2">Clave Unidad: <span x-text="p.clave_unidad_id"></span></span>
                                                <span>Precio: $<span x-text="Number(p.precio||0).toFixed(2)"></span></span>
                                            </div>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="col-span-12 lg:col-span-4 flex items-end justify-end">
                            <div class="text-[11px] text-gray-500 pr-1">Renglones: </div>
                            <div class="text-sm font-semibold" x-text="conceptos.length"></div>
                        </div>
                    </div>
                </div>

                {{-- Tabla conceptos --}}
                <div class="mt-4 overflow-x-auto">
                    <table class="table-auto w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-2 py-2 text-left">Descripción</th>
                                <th class="px-2 py-2 text-left">Clave PS</th>
                                <th class="px-2 py-2 text-left">Clave U</th>
                                <th class="px-2 py-2 text-left">Unidad</th>
                                <th class="px-2 py-2 text-right">Cantidad</th>
                                <th class="px-2 py-2 text-right">Precio</th>
                                <th class="px-2 py-2 text-right">Desc</th>
                                <th class="px-2 py-2 text-right">Imps</th>
                                <th class="px-2 py-2 text-right">Importe</th>
                                <th class="px-2 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="conceptos.length === 0">
                                <tr>
                                    <td class="px-2 py-6 text-center text-gray-500" colspan="10">
                                        Agrega productos con el buscador de arriba.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(c,i) in conceptos" :key="i">
                                <tr class="border-t border-gray-100 dark:border-gray-700/60">
                                    <td class="px-2 py-2 w-[28rem]">
                                        <input class="form-input w-full" x-model="c.descripcion">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input class="form-input w-28" x-model="c.clave_prod_serv_id" placeholder="01010101">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input class="form-input w-24" x-model="c.clave_unidad_id" placeholder="H87">
                                    </td>
                                    <td class="px-2 py-2">
                                        <input class="form-input w-24" x-model="c.unidad" placeholder="Pieza">
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <input class="form-input w-24 text-right" type="number" step="0.001" min="0" x-model.number="c.cantidad">
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <input class="form-input w-28 text-right" type="number" step="0.0001" min="0" x-model.number="c.precio">
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <input class="form-input w-24 text-right" type="number" step="0.01" min="0" x-model.number="c.descuento">
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <button type="button" class="btn-xs bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded"
                                                @click="abrirImpuesto(i)">
                                            + Imp.
                                        </button>
                                        <div class="text-[11px] text-gray-500" x-text="c.impuestos.length + ' agregado(s)'"></div>
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <span x-text="lineaImporte(c).toFixed(2)"></span>
                                    </td>
                                    <td class="px-2 py-2 text-right">
                                        <button type="button" class="btn-xs bg-rose-100 hover:bg-rose-200 text-rose-700 rounded"
                                                @click="eliminarConcepto(i)">
                                            Quitar
                                        </button>
                                    </td>
                                </tr>
                                {{-- Lista de impuestos del renglón --}}
                                <tr x-show="c.impuestos.length" class="bg-slate-50/50 dark:bg-gray-900/40">
                                    <td colspan="10" class="px-2 py-2">
                                        <div class="flex flex-wrap gap-2">
                                            <template x-for="(imp,j) in c.impuestos" :key="j">
                                                <div class="px-2 py-1 text-xs rounded border border-gray-200 dark:border-gray-700/60">
                                                    <span x-text="imp.tipo === 'trasladado' ? 'Tras.' : 'Ret.'"></span> ·
                                                    <span x-text="imp.impuesto"></span> ·
                                                    <span x-text="imp.base_modo === 'porc' ? ((Number(imp.tasa||0)*100).toFixed(2)+'%') : ('$'+Number(imp.valor||0).toFixed(2))"></span>
                                                    <button type="button" class="ml-2 text-rose-600 hover:underline"
                                                            @click="eliminarImpuesto(i,j)">remover</button>
                                                </div>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal pequeño para agregar impuesto a un concepto --}}
            <div class="fixed inset-0 z-40 flex items-center justify-center p-4"
                 x-show="abrirModalImpuestoIdx !== null"
                 x-transition
                 @keydown.escape.window="cerrarImpuesto()"
                 style="display:none">
                <div class="absolute inset-0 bg-black/40" @click="cerrarImpuesto()"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-lg w-full max-w-md p-4 sm:p-6">
                    <div class="text-lg font-semibold mb-3">Agregar impuesto</div>
                    <div class="grid grid-cols-6 gap-3">
                        <div class="col-span-6 sm:col-span-3">
                            <x-label class="mb-1">Tipo</x-label>
                            <select class="form-select w-full" x-model="nuevoImp.tipo">
                                <option value="trasladado">Trasladado</option>
                                <option value="retencion">Retención</option>
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <x-label class="mb-1">Impuesto</x-label>
                            <select class="form-select w-full" x-model="nuevoImp.impuesto">
                                <option value="002">IVA (002)</option>
                                <option value="001">ISR (001)</option>
                                <option value="003">IEPS (003)</option>
                            </select>
                        </div>

                        <div class="col-span-6 sm:col-span-3">
                            <x-label class="mb-1">Base</x-label>
                            <select class="form-select w-full" x-model="nuevoImp.base_modo">
                                <option value="porc">% sobre base</option>
                                <option value="monto">Monto fijo</option>
                            </select>
                        </div>

                        <template x-if="nuevoImp.base_modo === 'porc'">
                            <div class="col-span-6 sm:col-span-3">
                                <x-label class="mb-1">Tasa</x-label>
                                <select class="form-select w-full" x-model="nuevoImp.tasa">
                                    <option value="0.000000">0%</option>
                                    <option value="0.080000">8%</option>
                                    <option value="0.160000">16%</option>
                                </select>
                            </div>
                        </template>
                        <template x-if="nuevoImp.base_modo === 'monto'">
                            <div class="col-span-6 sm:col-span-3">
                                <x-label class="mb-1">Monto</x-label>
                                <input type="number" step="0.01" min="0" class="form-input w-full" x-model="nuevoImp.valor">
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button type="button" class="btn-sm bg-slate-100 hover:bg-slate-200 rounded-lg"
                                @click="cerrarImpuesto()">Cancelar</button>
                        <button type="button" class="btn-sm bg-violet-500 hover:bg-violet-600 text-white rounded-lg"
                                @click="agregarImpuesto()">Agregar</button>
                    </div>
                </div>
            </div>

            {{-- ========== Sección 4: Impuestos globales (opcionales) ========== --}}
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-semibold">Impuestos globales</div>
                    <button type="button" class="btn-xs bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded"
                            @click="agregarGlobal()">+ Agregar</button>
                </div>
                <div class="mt-3 space-y-2" x-show="globales.length">
                    <template x-for="(g,i) in globales" :key="i">
                        <div class="flex flex-wrap items-center gap-3 border border-gray-200 dark:border-gray-700/60 rounded-lg p-2">
                            <select class="form-select" x-model="g.tipo">
                                <option value="trasladado">Trasladado</option>
                                <option value="retencion">Retención</option>
                            </select>
                            <select class="form-select" x-model="g.impuesto">
                                <option value="002">IVA (002)</option>
                                <option value="001">ISR (001)</option>
                                <option value="003">IEPS (003)</option>
                            </select>
                            <select class="form-select" x-model="g.base_modo">
                                <option value="porc">% sobre base</option>
                                <option value="monto">Monto fijo</option>
                            </select>
                            <template x-if="g.base_modo === 'porc'">
                                <select class="form-select" x-model="g.tasa">
                                    <option value="0.000000">0%</option>
                                    <option value="0.080000">8%</option>
                                    <option value="0.160000">16%</option>
                                </select>
                            </template>
                            <template x-if="g.base_modo === 'monto'">
                                <input type="number" step="0.01" min="0" class="form-input w-28" x-model="g.valor">
                            </template>

                            <button type="button" class="ml-auto btn-xs bg-rose-100 hover:bg-rose-200 text-rose-700 rounded"
                                    @click="borrarGlobal(i)">Quitar</button>
                        </div>
                    </template>
                </div>
                <div x-show="!globales.length" class="text-sm text-gray-500 mt-2">No hay impuestos globales agregados.</div>
            </div>

            {{-- ========== Sección 5: Documentos relacionados ========== --}}
            <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 sm:col-span-3">
                        <x-label class="mb-1">Tipo relación</x-label>
                        <select class="form-select w-full" x-model="relacionTipo">
                            <option value="01">01 - Nota de crédito</option>
                            <option value="02">02 - Nota de débito</option>
                            <option value="03">03 - Devolución</option>
                            <option value="04">04 - Sustitución</option>
                            <option value="07">07 - CFDI por aplicación de anticipo</option>
                        </select>
                    </div>
                    <div class="col-span-12 sm:col-span-6">
                        <x-label class="mb-1">UUID</x-label>
                        <input type="text" class="form-input w-full uppercase" x-model="relacionUUID" placeholder="Ej. A1B2C3...">
                    </div>
                    <div class="col-span-12 sm:col-span-3 flex items-end">
                        <button type="button" class="btn-sm bg-slate-100 hover:bg-slate-200 rounded-lg w-full"
                                @click="agregarRelacion()">Agregar</button>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto" x-show="relaciones.length">
                    <table class="table-auto w-full text-sm">
                        <thead class="text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-2 py-2 text-left">Tipo</th>
                                <th class="px-2 py-2 text-left">UUID</th>
                                <th class="px-2 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(r,idx) in relaciones" :key="idx">
                                <tr class="border-t border-gray-100 dark:border-gray-700/60">
                                    <td class="px-2 py-2" x-text="r.tipo"></td>
                                    <td class="px-2 py-2" x-text="r.uuid"></td>
                                    <td class="px-2 py-2 text-right">
                                        <button type="button" class="btn-xs bg-rose-100 hover:bg-rose-200 text-rose-700 rounded"
                                                @click="borrarRelacion(idx)">Quitar</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="!relaciones.length" class="text-sm text-gray-500 mt-2">Sin documentos relacionados.</div>
            </div>

            {{-- ========== Totales + Acciones ========== --}}
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12 lg:col-span-6">
                    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                        <div class="text-sm font-semibold mb-2">Totales</div>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <div>Subtotal</div>
                                <div class="font-medium" x-text="totalSubtotal().toFixed(2)"></div>
                            </div>
                            <div class="flex justify-between">
                                <div>Impuestos trasladados</div>
                                <div class="font-medium" x-text="totalImpuestosTras().toFixed(2)"></div>
                            </div>
                            <div class="flex justify-between">
                                <div>Impuestos retenidos</div>
                                <div class="font-medium" x-text="totalImpuestosRet().toFixed(2)"></div>
                            </div>
                            <div class="flex justify-between text-lg">
                                <div class="font-semibold">Total</div>
                                <div class="font-bold" x-text="totalGeneral().toFixed(2)"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-span-12 lg:col-span-6">
                    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4 sm:p-6">
                        <div class="flex flex-wrap gap-2 justify-end">
                            <button type="button" class="btn bg-slate-100 hover:bg-slate-200 rounded-lg"
                                    @click="submitPreview()">Visualizar</button>
                            <button type="button" class="btn bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg"
                                    @click="submitGuardar()">Guardar prefactura</button>
                            <button type="button" class="btn bg-violet-500 hover:bg-violet-600 text-white rounded-lg"
                                    @click="submitTimbrar()">Timbrar</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ====== Campos ocultos para submit (si quieres serializar arrays) ======
                 Puedes serializar como JSON y el controlador parsea. --}}
            <input type="hidden" name="payload[tipo_comprobante]" x-model="form.tipo_comprobante">
            <input type="hidden" name="payload[serie]" x-model="form.serie">
            <input type="hidden" name="payload[folio]" x-model="form.folio">
            <input type="hidden" name="payload[fecha]" x-model="form.fecha">
            <input type="hidden" name="payload[forma_pago]" x-model="form.forma_pago">
            <input type="hidden" name="payload[metodo_pago]" x-model="form.metodo_pago">
            <input type="hidden" name="payload[moneda]" x-model="form.moneda">
            <input type="hidden" name="payload[cliente_id]" x-model="form.cliente_id">
            <input type="hidden" name="payload[comentarios_pdf]" x-model="form.comentarios_pdf">

            <textarea name="payload[conceptos]" x-text="JSON.stringify(conceptos)" class="hidden"></textarea>
            <textarea name="payload[relaciones]" x-text="JSON.stringify(relaciones)" class="hidden"></textarea>
            <textarea name="payload[globales]"   x-text="JSON.stringify(globales)"   class="hidden"></textarea>
        </form>

        {{-- Drawer lateral para actualizar cliente (estilo plantilla) --}}
        <div class="fixed inset-0 z-40" x-show="showClienteDrawer" x-transition style="display:none">
            <div class="absolute inset-0 bg-black/40" @click="cerrarDrawerCliente()"></div>
            <div class="absolute top-0 right-0 h-full w-full sm:w-[480px] bg-white dark:bg-gray-800 shadow-xl p-4 sm:p-6 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-lg font-semibold">Actualizar cliente</div>
                    <button class="text-gray-500 hover:text-gray-700" @click="cerrarDrawerCliente()">
                        ✕
                    </button>
                </div>
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12">
                        <x-label class="mb-1">Razón social</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.razon_social">
                    </div>
                    <div class="col-span-6">
                        <x-label class="mb-1">RFC</x-label>
                        <input class="form-input w-full uppercase" x-model="clienteEdit.rfc">
                    </div>
                    <div class="col-span-6">
                        <x-label class="mb-1">Email</x-label>
                        <input class="form-input w-full" type="email" x-model="clienteEdit.email">
                    </div>

                    <div class="col-span-8">
                        <x-label class="mb-1">Calle</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.calle">
                    </div>
                    <div class="col-span-4">
                        <x-label class="mb-1">No. Ext</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.no_ext">
                    </div>
                    <div class="col-span-4">
                        <x-label class="mb-1">No. Int</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.no_int">
                    </div>
                    <div class="col-span-8">
                        <x-label class="mb-1">Colonia</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.colonia">
                    </div>

                    <div class="col-span-6">
                        <x-label class="mb-1">Localidad</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.localidad">
                    </div>
                    <div class="col-span-6">
                        <x-label class="mb-1">Estado</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.estado">
                    </div>

                    <div class="col-span-6">
                        <x-label class="mb-1">País</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.pais">
                    </div>
                    <div class="col-span-6">
                        <x-label class="mb-1">Código Postal</x-label>
                        <input class="form-input w-full" x-model="clienteEdit.codigo_postal">
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" class="btn bg-slate-100 hover:bg-slate-200 rounded-lg"
                            @click="cerrarDrawerCliente()">Cancelar</button>
                    <button type="button" class="btn bg-violet-500 hover:bg-violet-600 text-white rounded-lg"
                            @click="guardarCliente()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
