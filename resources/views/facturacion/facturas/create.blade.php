@extends('layouts.app')

@section('title','Nueva Factura')

@section('content')
@php
  $rfcUsuarioId = $rfcUsuarioId ?? (session('rfc_usuario_id') ?? session('rfc_activo_id') ?? 0);
  $minFecha = $minFecha ?? now()->copy()->subHours(72)->format('Y-m-d\TH:i');
  $maxFecha = $maxFecha ?? now()->format('Y-m-d\TH:i');
  $impuestosCfg = config('impuestos');
@endphp

<div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">

  <div class="flex items-start justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Nueva Factura</h1>
    <div class="flex items-center gap-2">
      <span class="text-xs uppercase text-gray-400 dark:text-gray-500">RFC activo</span>
      <div class="px-2 py-1 rounded bg-violet-500/10 text-violet-600 dark:text-violet-400 text-sm">{{ $rfcActivo ?? '-' }}</div>
    </div>
  </div>

  <div id="facturaRoot"
       data-rfc-id="{{ (int)$rfcUsuarioId }}"
       data-next-folio-url="{{ url('/facturas/next-folio') }}"
       data-prod-buscar-url="{{ url('/api/productos/buscar') }}"
       data-clave-prod-serv-url="{{ url('/productos/clave-prod-serv') }}"
       data-clave-unidad-url="{{ url('/productos/clave-unidad') }}"
       data-clientes='@json(($clientes ?? collect())->map(fn($c)=>["id"=>$c->id,"razon_social"=>$c->razon_social,"rfc"=>$c->rfc,"email"=>$c->email,"calle"=>$c->calle,"no_ext"=>$c->no_ext,"no_int"=>$c->no_int,"colonia"=>$c->colonia,"localidad"=>$c->localidad,"estado"=>$c->estado,"codigo_postal"=>$c->codigo_postal,"pais"=>$c->pais])->values())'
       data-csrf="{{ csrf_token() }}"
       class="space-y-6">

    {{-- Datos del comprobante --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Datos del comprobante</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label for="tipoComprobante" class="block text-sm font-medium mb-1">Tipo de comprobante</label>
          <select id="tipoComprobante" class="form-select w-full">
            <option value="I" selected>Ingreso</option>
            <option value="E">Egreso</option>
            <option value="T">Traslado</option>
            <option value="N">Nómina</option>
            <option value="P">Pago</option>
          </select>
        </div>
        <div>
          <label for="serie" class="block text-sm font-medium mb-1">Serie</label>
          <input id="serie" type="text" class="form-input w-full" readonly>
        </div>
        <div>
          <div class="flex items-center justify-between">
            <label for="folio" class="block text-sm font-medium mb-1">Folio</label>
            <button id="btnActualizarFolio" type="button" class="text-xs text-violet-600 hover:text-violet-700">Actualizar</button>
          </div>
          <input id="folio" type="text" class="form-input w-full" readonly>
        </div>
        <div>
          <label for="fecha" class="block text-sm font-medium mb-1">Fecha y hora</label>
          <input id="fecha" type="datetime-local" class="form-input w-full" min="{{ $minFecha }}" max="{{ $maxFecha }}" value="{{ $maxFecha }}">
        </div>
        <div>
          <label for="metodoPago" class="block text-sm font-medium mb-1">Método de pago</label>
          <select id="metodoPago" class="form-select w-full">
            <option value="PUE" selected>PUE</option>
            <option value="PPD">PPD</option>
          </select>
        </div>
        <div>
          <label for="formaPago" class="block text-sm font-medium mb-1">Forma de pago</label>
          <select id="formaPago" class="form-select w-full">
            <option value="01">01 - Efectivo</option>
            <option value="02">02 - Cheque</option>
            <option value="03" selected>03 - Transferencia</option>
            <option value="04">04 - Tarjeta de crédito</option>
            <option value="28">28 - Tarjeta de débito</option>
            <option value="99">99 - Por definir</option>
          </select>
        </div>
        <div class="md:col-span-3">
          <label for="comentariosPdf" class="block text-sm font-medium mb-1">Comentarios en PDF</label>
          <textarea id="comentariosPdf" rows="2" class="form-textarea w-full" placeholder="Notas visibles en el PDF"></textarea>
        </div>
      </div>
    </div>

    {{-- Cliente --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Cliente</h2>
        <div class="flex items-center gap-2">
          <div class="w-64">
            <select id="cliente_id" class="form-select w-full">
              <option value="">— Selecciona —</option>
              @foreach(($clientes ?? []) as $c)
                <option value="{{ $c->id }}">{{ $c->razon_social }} · {{ $c->rfc }}</option>
              @endforeach
            </select>
          </div>
          <button id="btnClienteEditar" type="button" disabled class="btn-sm bg-violet-500 hover:bg-violet-600 text-white">Actualizar</button>
        </div>
      </div>
      <div id="clienteResumen" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-y-1 gap-x-6 text-sm"></div>
    </div>

    {{-- Productos: buscador fijo + tabla resultados --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div id="productosSearchBar" class="border-b border-gray-200 dark:border-gray-700/60 pb-3 mb-3">
        <label class="block text-sm font-medium mb-1" for="buscarProducto">Buscar producto</label>
        <input id="buscarProducto" type="text" class="form-input w-full" placeholder="Código o texto" autocomplete="off">
      </div>
      <div class="overflow-x-auto">
        <table class="table-auto w-full text-sm" id="tablaProductos">
          <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
              <th class="px-2 py-2 text-left">Descripción</th>
              <th class="px-2 py-2 text-left">Unidad</th>
              <th class="px-2 py-2 text-right">Precio</th>
            </tr>
          </thead>
          <tbody id="productosBody"></tbody>
        </table>
      </div>
    </div>

    {{-- Conceptos --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Conceptos</h2>
        <button id="btnAgregarConcepto" type="button" class="btn-sm bg-gray-900 dark:bg-white dark:text-gray-900 text-white hover:opacity-90">Agregar concepto</button>
      </div>
      <div class="overflow-x-auto">
        <table class="table w-full text-sm" id="tablaConceptos">
          <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
            <tr>
              <th class="px-2 py-2 text-left">Descripción</th>
              <th class="px-2 py-2 text-right">Cantidad</th>
              <th class="px-2 py-2 text-right">Precio unitario</th>
              <th class="px-2 py-2 text-right">Importe</th>
              <th class="px-2 py-2 text-left">Claves SAT</th>
              <th class="px-2 py-2">Impuestos</th>
              <th class="px-2 py-2"></th>
            </tr>
          </thead>
          <tbody id="conceptosBody"></tbody>
        </table>
      </div>
    </div>

    {{-- Impuestos globales --}}
    <div class="bg-white dark:bg-gray-800 shadow-xs rounded-xl p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Impuestos globales</h2>
        <button id="btnImpuestoGlobalAdd" type="button" class="btn-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:opacity-90">Agregar impuesto global</button>
      </div>
      <div id="impuestosGlobales" class="space-y-2"></div>
    </div>

    {{-- Totales --}}
    <div class="flex justify-end">
      <div class="w-full max-w-sm space-y-1 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span id="subtotalGeneral">$0.00</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Trasladados</span><span id="totalImpuestosTrasladados">$0.00</span></div>
        <div class="flex justify-between"><span class="text-gray-500">Retenidos</span><span id="totalImpuestosRetenidos">$0.00</span></div>
        <div class="flex justify-between font-semibold text-gray-800 dark:text-gray-100"><span>Total</span><span id="totalGeneral">$0.00</span></div>
      </div>
    </div>

    {{-- Acciones --}}
    <div class="flex items-center justify-end gap-3">
      <button id="btnPreview" type="button" class="btn border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">Visualizar</button>
      <button id="btnGuardar" type="button" class="btn bg-gray-900 dark:bg-white dark:text-gray-900 text-white hover:opacity-90">Guardar (prefactura)</button>
      <button id="btnTimbrar" type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white">Timbrar</button>
    </div>

  </div>
</div>

{{-- Slide-over Cliente --}}
<div id="drawerCliente" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/30" data-close="drawer"></div>
  <div class="absolute right-0 top-0 h-full w-full max-w-xl bg-white dark:bg-gray-900 shadow-xl p-4 overflow-y-auto">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-lg font-semibold">Editar cliente</h3>
      <button id="drawerClienteClose" type="button" class="text-gray-500 hover:text-gray-700">Cerrar</button>
    </div>
    <div id="drawerClienteBody">
      @if (view()->exists('catalogos.clientes._form'))
        @include('catalogos.clientes._form', ['cliente' => null])
      @else
        <p class="text-sm text-gray-500">Formulario de cliente no disponible como parcial. Puedes editar en la página de clientes.</p>
      @endif
    </div>
    <div class="mt-4 flex items-center justify-end gap-2">
      <button id="drawerClienteSave" type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white">Guardar</button>
    </div>
  </div>
</div>

{{-- Modal Impuesto por concepto --}}
<div id="modalImpuestoConcepto" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/30" data-close="modal"></div>
  <div class="absolute inset-0 m-auto w-full max-w-md bg-white dark:bg-gray-900 rounded-lg shadow p-4 h-fit">
    <h3 class="text-lg font-semibold mb-3">Agregar impuesto (concepto)</h3>
    <div class="grid grid-cols-1 gap-3">
      <div>
        <label for="impTipo" class="block text-sm mb-1">Tipo</label>
        <select id="impTipo" class="form-select w-full">
          <option value="trasladado">Trasladado</option>
          <option value="retencion">Retención</option>
        </select>
      </div>
      <div>
        <label for="impImpuesto" class="block text-sm mb-1">Impuesto</label>
        <select id="impImpuesto" class="form-select w-full"></select>
      </div>
      <div>
        <label for="impTasa" class="block text-sm mb-1">Tasa</label>
        <select id="impTasa" class="form-select w-full"></select>
      </div>
    </div>
    <div class="mt-4 flex justify-end gap-2">
      <button id="impConceptoCancel" type="button" class="btn">Cancelar</button>
      <button id="impConceptoConfirm" type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white">Agregar</button>
    </div>
  </div>
  <script id="impuestos-config" type="application/json">@json($impuestosCfg)</script>
  <script id="clientes-json" type="application/json">@json(($clientes ?? collect())->values())</script>
  <script id="fechas-config" type="application/json">@json(['min'=>$minFecha,'max'=>$maxFecha])</script>
  <script id="rutas-config" type="application/json">@json([
    'nextFolio'=>url('/facturas/next-folio'),
    'prodBuscar'=>url('/api/productos/buscar'),
    'claveProdServ'=>url('/productos/clave-prod-serv'),
    'claveUnidad'=>url('/productos/clave-unidad'),
    'clientesBase'=>url('/catalogos/clientes'),
  ])</script>
</div>

{{-- Modal Impuesto global --}}
<div id="modalImpuestoGlobal" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/30" data-close="modal"></div>
  <div class="absolute inset-0 m-auto w-full max-w-md bg-white dark:bg-gray-900 rounded-lg shadow p-4 h-fit">
    <h3 class="text-lg font-semibold mb-3">Agregar impuesto global</h3>
    <div class="grid grid-cols-1 gap-3">
      <div>
        <label for="impTipoG" class="block text-sm mb-1">Tipo</label>
        <select id="impTipoG" class="form-select w-full">
          <option value="trasladado">Trasladado</option>
          <option value="retencion">Retención</option>
        </select>
      </div>
      <div>
        <label for="impImpuestoG" class="block text-sm mb-1">Impuesto</label>
        <select id="impImpuestoG" class="form-select w-full"></select>
      </div>
      <div>
        <label for="impTasaG" class="block text-sm mb-1">Tasa</label>
        <select id="impTasaG" class="form-select w-full"></select>
      </div>
    </div>
    <div class="mt-4 flex justify-end gap-2">
      <button id="impGlobalCancel" type="button" class="btn">Cancelar</button>
      <button id="impGlobalConfirm" type="button" class="btn bg-violet-600 hover:bg-violet-700 text-white">Agregar</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/facturas/create.js') }}"></script>
@endpush

