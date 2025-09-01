@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 space-y-6" x-data="uiFactura()">

    @if(session('ok'))
        <div class="bg-green-50 text-green-800 px-4 py-2 rounded-lg">{{ session('ok') }}</div>
    @endif

    <!-- Encabezado -->
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Nueva Factura (CFDI 4.0)</h1>

        <div class="grid sm:grid-cols-4 gap-4">
            <div class="sm:col-span-2">
                <label class="text-xs text-gray-500">Emisor</label>
                <input class="form-input" value="{{ $emisor->razon_social ?? '' }}" readonly>
            </div>
            <div>
                <label class="text-xs text-gray-500">Código Postal</label>
                <input class="form-input" x-model="form.lugarExpedicion" value="{{ $emisor->codigo_postal ?? '' }}">
            </div>
            <div>
                <label class="text-xs text-gray-500">Fecha y hora</label>
                <input type="datetime-local" class="form-input" x-model="form.fecha">
            </div>

            <div>
                <label class="text-xs text-gray-500">Tipo comprobante</label>
                <select class="form-select" x-model="form.tipoComprobante">
                    @foreach($tiposComprobante as $k=>$v)
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
                <select class="form-select" x-model="form.moneda">
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
            <div class="sm:col-span-1">
                <label class="text-xs text-gray-500">Comentarios PDF</label>
                <input class="form-input" x-model="form.comentarios_pdf" placeholder="Opcional">
            </div>
        </div>
    </div>

    <!-- Cliente -->
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="sm:col-span-1">
                <label class="text-xs text-gray-500">Cliente</label>
                <select class="form-select" x-model="form.cliente_id">
                    <template x-for="c in clientes" :key="c.id">
                        <option :value="c.id" x-text="c.razon_social + ' ('+c.rfc+')'"></option>
                    </template>
                </select>
            </div>
            <div class="sm:col-span-2">
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-700/40 text-sm">
                    <div class="font-medium text-gray-700 dark:text-gray-200" x-text="clienteActual?.razon_social || '—'"></div>
                    <div class="text-gray-500 dark:text-gray-400">
                        RFC: <span x-text="clienteActual?.rfc || '—'"></span> ·
                        CP: <span x-text="clienteActual?.codigo_postal || '—'"></span> ·
                        Régimen: <span x-text="clienteActual?.regimen_fiscal || '—'"></span> ·
                        Email: <span x-text="clienteActual?.email || '—'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conceptos -->
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Conceptos</h3>
            <div class="space-x-2">
                <button class="btn btn-sm" @click="agregar()">+ Concepto</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-gray-500">
                    <tr>
                        <th class="px-2 py-2 text-left w-64">Descripción</th>
                        <th class="px-2 py-2">ClaveProdServ</th>
                        <th class="px-2 py-2">ClaveUnidad</th>
                        <th class="px-2 py-2">Unidad</th>
                        <th class="px-2 py-2">Cant.</th>
                        <th class="px-2 py-2">Precio</th>
                        <th class="px-2 py-2">Desc</th>
                        <th class="px-2 py-2">IVA %</th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(it, i) in conceptos" :key="i">
                        <tr class="border-t border-gray-200 dark:border-gray-700/60">
                            <td class="px-2 py-1"><input class="form-input" x-model="it.descripcion"></td>
                            <td class="px-2 py-1 w-32"><input class="form-input" x-model="it.clave_prod_serv" placeholder="01010101"></td>
                            <td class="px-2 py-1 w-24"><input class="form-input" x-model="it.clave_unidad" placeholder="H87"></td>
                            <td class="px-2 py-1 w-24"><input class="form-input" x-model="it.unidad" placeholder="Pieza"></td>
                            <td class="px-2 py-1 w-20"><input class="form-input text-right" type="number" step="0.001" x-model.number="it.cantidad"></td>
                            <td class="px-2 py-1 w-28"><input class="form-input text-right" type="number" step="0.01"  x-model.number="it.precio"></td>
                            <td class="px-2 py-1 w-24"><input class="form-input text-right" type="number" step="0.01"  x-model.number="it.descuento"></td>
                            <td class="px-2 py-1 w-20"><input class="form-input text-right" type="number" step="0.01"  x-model.number="it.iva"></td>
                            <td class="px-2 py-1"><button class="text-red-500" @click="conceptos.splice(i,1)">Quitar</button></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="mt-3 text-right text-sm text-gray-700 dark:text-gray-200">
            Subtotal: <strong x-text="fmt(totales.sub)"></strong> ·
            Descuento: <strong x-text="fmt(totales.desc)"></strong> ·
            IVA: <strong x-text="fmt(totales.iva)"></strong> ·
            TOTAL: <strong x-text="fmt(totales.total)"></strong>
        </div>
    </div>

    <!-- Relaciones y notas -->
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-5">
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="text-xs text-gray-500">Tipo de relación</label>
                <select class="form-select" x-model="form.tipoRelacion">
                    <option value="">—</option>
                    @foreach($tiposRelacion as $k=>$v)
                        <option value="{{ $k }}">{{ $k }} - {{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs text-gray-500">UUID(s) relacionados</label>
                <textarea class="form-textarea" rows="2" x-model="form.uuidsRelacionados" placeholder="Uno por línea"></textarea>
            </div>
            <div class="sm:col-span-3">
                <label class="text-xs text-gray-500">Comentarios PDF</label>
                <input class="form-input" x-model="form.comentarios_pdf" placeholder="Opcional">
            </div>
        </div>
    </div>

    <!-- Submit -->
    <form method="POST" action="{{ route('facturas.store') }}" @submit.prevent="enviar($event)">
        @csrf
        <template x-for="(h, k) in payload" :key="k">
            <input type="hidden" :name="h.name" :value="h.value">
        </template>

        <div class="flex justify-end gap-3">
            <button class="btn" type="button" @click="accion='borrador'; enviar()">Guardar borrador</button>
            <button class="btn btn-primary" type="button" @click="accion='timbrar'; enviar()">Timbrar</button>
        </div>
    </form>
</div>

<script>
function uiFactura(){
    return {
        clientes: @json($clientes),
        productos: @json($productos),
        form: {
            fecha: new Date().toISOString().slice(0,16),
            tipoComprobante: 'I',
            serie: '', folio: '',
            usoCFDI: 'G03',
            moneda: 'MXN',
            formaPago: '03',
            metodoPago: 'PUE',
            lugarExpedicion: '{{ $emisor->codigo_postal ?? '' }}',
            cliente_id: @json($clientes->first()->id ?? null),
            comentarios_pdf: '',
            tipoRelacion: '',
            uuidsRelacionados: '',
        },
        conceptos: [{
            descripcion:'Servicio',
            clave_prod_serv:'01010101',
            clave_unidad:'H87',
            unidad:'Pieza',
            cantidad:1, precio:0, descuento:0, iva:16,
        }],
        accion: 'timbrar',
        get clienteActual(){
            return this.clientes.find(c => c.id == this.form.cliente_id) || null;
        },
        agregar(){ this.conceptos.push({descripcion:'',clave_prod_serv:'01010101',clave_unidad:'H87',unidad:'',cantidad:1,precio:0,descuento:0,iva:16}); },
        get totales(){
            let sub=0, desc=0, iva=0;
            this.conceptos.forEach(it=>{
                const imp = (Number(it.cantidad)||0) * (Number(it.precio)||0);
                const d   = (Number(it.descuento)||0);
                const base= Math.max(imp - d, 0);
                sub  += imp; desc += d; iva += base * (Number(it.iva)||0)/100;
            });
            return {sub, desc, iva, total: sub - desc + iva};
        },
        fmt(n){ return new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(n||0) },
        get payload(){
            const out = [];
            const push = (name,val)=> Array.isArray(val) ? val.forEach(v=>out.push({name, value:v})) : out.push({name, value:val});

            // Campos “planos”
            Object.entries(this.form).forEach(([k,v])=> push(k,v));
            push('accion', this.accion);

            // Conceptos
            this.conceptos.forEach((c,i)=>{
                push(`conceptos[${i}][descripcion]`,     c.descripcion);
                push(`conceptos[${i}][clave_prod_serv]`, c.clave_prod_serv);
                push(`conceptos[${i}][clave_unidad]`,    c.clave_unidad);
                push(`conceptos[${i}][unidad]`,          c.unidad);
                push(`conceptos[${i}][cantidad]`,        c.cantidad);
                push(`conceptos[${i}][precio]`,          c.precio);
                push(`conceptos[${i}][descuento]`,       c.descuento);
                push(`conceptos[${i}][iva]`,             c.iva);
            });

            return out;
        },
        enviar(){
            // Validación mínima
            if(!this.form.cliente_id) return alert('Selecciona un cliente.');
            if(!this.conceptos.length) return alert('Agrega al menos un concepto.');
            // Submit real
            const f = document.querySelector('form[action="{{ route('facturas.store') }}"]');
            f.submit();
        }
    }
}
</script>
@endsection
