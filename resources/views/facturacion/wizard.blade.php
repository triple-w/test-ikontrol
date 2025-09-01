@extends('layouts.app') {{-- usa tu layout principal --}}

@section('content')
<div class="max-w-7xl mx-auto p-4 space-y-6" x-data="wizardFactura()">

    {{-- Paso 1: Tipo de documento y datos receptor --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Nueva factura / CFDI</h2>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500">Tipo de comprobante</label>
                <select class="form-select" x-model="form.tipo" name="tipo">
                    <option value="I">Ingreso (Factura/Honorarios/Arrendamiento)</option>
                    <option value="E">Egreso (Nota de crédito)</option>
                    <option value="P">Pago (Complemento de pago)</option>
                    <option value="T">Traslado</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Cliente</label>
                <select class="form-select" x-model="form.cliente_id">
                    <template x-for="c in clientes" :key="c.id">
                        <option :value="c.id" x-text="c.razon_social + ' ('+c.rfc+')'"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Uso CFDI</label>
                <select class="form-select" x-model="form.usoCFDI">
                    @foreach($usosCfdi as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Moneda</label>
                <select class="form-select" x-model="form.tipoMoneda">
                    @foreach($monedas as $m)
                        <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Forma de pago</label>
                <select class="form-select" x-model="form.formaPago">
                    <option value="">—</option>
                    @foreach($formasPago as $k=>$v)
                        <option value="{{ $k }}">{{ $k }} - {{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Método de pago</label>
                <select class="form-select" x-model="form.metodoPago">
                    @foreach($metodos as $k=>$v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="text-xs text-gray-500">Serie</label>
                <input class="form-input" x-model="form.serie" placeholder="Opcional">
            </div>
            <div>
                <label class="text-xs text-gray-500">Folio</label>
                <input class="form-input" x-model="form.folio" placeholder="Opcional">
            </div>

            <div class="sm:col-span-2">
                <label class="text-xs text-gray-500">Fecha de emisión</label>
                <input type="datetime-local" class="form-input" x-model="form.fechaFactura">
            </div>

            <div class="sm:col-span-2 text-xs text-gray-500">
                <div x-show="clienteActual">
                    <div class="mt-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40">
                        <div class="font-medium text-gray-700 dark:text-gray-200" x-text="clienteActual.razon_social"></div>
                        <div class="text-gray-500 dark:text-gray-400">
                            RFC: <span x-text="clienteActual.rfc"></span> · CP: <span x-text="clienteActual.codigo_postal"></span> · Régimen: <span x-text="clienteActual.regimen_fiscal || '—'"></span><br>
                            Email: <span x-text="clienteActual.email || '—'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Paso 2: Conceptos --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Conceptos</h3>
            <button class="btn btn-sm btn-primary" @click="agregarRenglon()">Agregar concepto</button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500">
                    <tr>
                        <th class="px-2 py-2 text-left">Descripción</th>
                        <th class="px-2 py-2">ClaveProdServ</th>
                        <th class="px-2 py-2">ClaveUnidad</th>
                        <th class="px-2 py-2">Unidad</th>
                        <th class="px-2 py-2">Cant.</th>
                        <th class="px-2 py-2">Precio</th>
                        <th class="px-2 py-2">Desc</th>
                        <th class="px-2 py-2">IVA%</th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(it, idx) in items" :key="idx">
                        <tr class="border-t border-gray-200 dark:border-gray-700/60">
                            <td class="px-2 py-1 w-64">
                                <input class="form-input" x-model="it.descripcion" placeholder="Descripción">
                            </td>
                            <td class="px-2 py-1 w-36">
                                <input class="form-input" x-model="it.clave_prod_serv" placeholder="01010101">
                            </td>
                            <td class="px-2 py-1 w-28">
                                <input class="form-input" x-model="it.clave_unidad" placeholder="H87">
                            </td>
                            <td class="px-2 py-1 w-28">
                                <input class="form-input" x-model="it.unidad" placeholder="Pieza">
                            </td>
                            <td class="px-2 py-1 w-20">
                                <input class="form-input text-right" x-model.number="it.cantidad" type="number" min="0" step="0.001">
                            </td>
                            <td class="px-2 py-1 w-28">
                                <input class="form-input text-right" x-model.number="it.precio" type="number" min="0" step="0.01">
                            </td>
                            <td class="px-2 py-1 w-24">
                                <input class="form-input text-right" x-model.number="it.descuento" type="number" min="0" step="0.01">
                            </td>
                            <td class="px-2 py-1 w-20">
                                <input class="form-input text-right" x-model.number="it.iva" type="number" min="0" step="0.01" placeholder="16">
                            </td>
                            <td class="px-2 py-1">
                                <button class="text-red-500" @click="items.splice(idx,1)">Quitar</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-3 text-right text-sm text-gray-700 dark:text-gray-200">
            Subtotal: <span x-text="fmt(totales.subtotal)"></span> · IVA: <span x-text="fmt(totales.iva)"></span> · Descuento: <span x-text="fmt(totales.descuento)"></span> · <strong>Total: <span x-text="fmt(totales.total)"></span></strong>
        </div>
    </div>

    {{-- Paso 3: Datos adicionales / relaciones --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="text-xs text-gray-500">Tipo de relación (si aplica)</label>
                <select class="form-select" x-model="form.tipoRelacion">
                    <option value="">—</option>
                    @foreach($tiposRelacion as $k=>$v)
                        <option value="{{ $k }}">{{ $k }} - {{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">UUID(s) relacionados (uno por línea)</label>
                <textarea class="form-textarea" x-model="form.uuidsRelacionados" rows="3" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX&#10;..."></textarea>
            </div>

            <div class="sm:col-span-2">
                <label class="text-xs text-gray-500">Comentarios PDF (opcional)</label>
                <input class="form-input" x-model="form.comentariosPDF">
            </div>
        </div>
    </div>

    {{-- Acciones --}}
    <div class="flex justify-end gap-3">
        {{-- Si luego quieres “Guardar borrador”, aquí haces POST a otra ruta y guardas estatus=borrador --}}
        <form :action="routes.timbrar" method="POST" @submit.prevent="enviar()">
            @csrf
            <input type="hidden" name="tipo"                 :value="form.tipo">
            <input type="hidden" name="cliente_id"           :value="form.cliente_id">
            <input type="hidden" name="usoCFDI"              :value="form.usoCFDI">
            <input type="hidden" name="tipoMoneda"           :value="form.tipoMoneda">
            <input type="hidden" name="formaPago"            :value="form.formaPago">
            <input type="hidden" name="metodoPago"           :value="form.metodoPago">
            <input type="hidden" name="serie"                :value="form.serie">
            <input type="hidden" name="folio"                :value="form.folio">
            <input type="hidden" name="fechaFactura"         :value="form.fechaFactura">
            <input type="hidden" name="comentariosPDF"       :value="form.comentariosPDF">
            <input type="hidden" name="tipoRelacion"         :value="form.tipoRelacion">
            <template x-for="h in payloadOculto" :key="h.name">
                <input type="hidden" :name="h.name" :value="h.value">
            </template>

            <button type="submit" class="btn btn-primary">
                Timbrar ahora
            </button>
        </form>
    </div>
</div>

{{-- Alpine logic --}}
<script>
function wizardFactura() {
    return {
        clientes: @json($clientes),
        productos: @json($productos),
        routes: {
            timbrar: "{{ route('facturas.wizard.timbrar') }}",
        },
        form: {
            tipo: 'I',
            cliente_id: @json($clientes->first()->id ?? null),
            usoCFDI: 'G03',
            tipoMoneda: 'MXN',
            formaPago: '03',
            metodoPago: 'PUE',
            serie: '',
            folio: '',
            fechaFactura: (new Date()).toISOString().slice(0,16),
            comentariosPDF: '',
            tipoRelacion: '',
            uuidsRelacionados: '',
        },
        items: [],
        get clienteActual() {
            return this.clientes.find(c => c.id == this.form.cliente_id) || null;
        },
        agregarRenglon(p = null) {
            this.items.push({
                descripcion: p?.descripcion || '',
                clave_prod_serv: p?.clave_prod_serv || '01010101',
                clave_unidad: p?.clave_unidad || 'H87',
                unidad: p?.unidad || 'Pieza',
                cantidad: 1,
                precio: p?.precio || 0,
                descuento: 0,
                iva: 16,
            });
        },
        get totales() {
            let subtotal=0, iva=0, desc=0;
            this.items.forEach(it=>{
                const imp = (Number(it.cantidad)||0) * (Number(it.precio)||0);
                const d   = (Number(it.descuento)||0);
                const base= Math.max(imp - d, 0);
                const iv  = base * (Number(it.iva)||0) / 100;
                subtotal += imp;
                desc     += d;
                iva      += iv;
            });
            return { subtotal, descuento: desc, iva, total: subtotal - desc + iva };
        },
        fmt(n){ return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(n||0) },
        get payloadOculto() {
            // Empaquetar en los nombres que tu motor espera:
            const hidden = [];

            // series paralelas:
            const cps  = [], cun = [], des = [], can = [], pre = [], dec = [];
            const tr_tasa = [], tr_tipo = [], rt_tasa = [], rt_tipo = [];

            this.items.forEach(it=>{
                cps.push(it.clave_prod_serv);
                cun.push(it.clave_unidad);
                des.push(it.descripcion);
                can.push(it.cantidad);
                pre.push(it.precio);
                dec.push(it.descuento);

                // IVA traslado tasa y tipo
                tr_tasa.push((Number(it.iva)||0) / 100); // ej. 0.16
                tr_tipo.push('Tasa');
                // Si quisieras retención, agregas aquí rt_tasa/rt_tipo
            });

            // conceptos
            hidden.push({name: 'claves-prods-servs[]', value: cps});
            hidden.push({name: 'claves-unidades[]',    value: cun});
            hidden.push({name: 'descripciones[]',      value: des});
            hidden.push({name: 'cantidad[]',           value: can});
            hidden.push({name: 'precios[]',            value: pre});
            hidden.push({name: 'descuentos[]',         value: dec});

            // impuestos (tu motor usa estos nombres)
            hidden.push({name: 'traslados-tasap[]', value: tr_tasa});
            hidden.push({name: 'traslados-tipop[]', value: tr_tipo});
            // (retenciones si aplican)
            if (rt_tasa.length) hidden.push({name:'retenciones-tasap[]', value: rt_tasa});
            if (rt_tipo.length) hidden.push({name:'retenciones-tipop[]', value: rt_tipo});

            // relaciones
            if (this.form.tipoRelacion && this.form.uuidsRelacionados.trim()) {
                hidden.push({name:'tipoRelacion', value:this.form.tipoRelacion});
                this.form.uuidsRelacionados.split(/\s+/).forEach(u=>{
                    if (u) hidden.push({name:'uuidrel[]', value:u});
                });
            }

            // Aplana arreglos al submit
            const flat = [];
            hidden.forEach(h=>{
                if (Array.isArray(h.value)) {
                    h.value.forEach(v=>{
                        flat.push({name: h.name, value: v});
                    });
                } else {
                    flat.push(h);
                }
            });
            return flat;
        },
        enviar() {
            // Validación mínima del lado cliente
            if (!this.form.cliente_id) return alert('Selecciona un cliente.');
            if (!this.items.length)    return alert('Agrega al menos un concepto.');
            // submit real
            $el = event.target.closest('form');
            $el.submit();
        }
    }
}
</script>
@endsection
