@php
  $estadosMx = ['Aguascalientes','Baja California','Baja California Sur','Campeche','Coahuila','Colima','Chiapas','Chihuahua','Ciudad de México','Durango','Guanajuato','Guerrero','Hidalgo','Jalisco','México','Michoacán','Morelos','Nayarit','Nuevo León','Oaxaca','Puebla','Querétaro','Quintana Roo','San Luis Potosí','Sinaloa','Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz','Yucatán','Zacatecas'];

  $catTipoContrato   = config('sat_nomina.tipo_contrato', []);
  $catTipoJornada    = config('sat_nomina.tipo_jornada', []);
  $catTipoRegimen    = config('sat_nomina.tipo_regimen', []);
  $catPeriodicidad   = config('sat_nomina.periodicidad_pago', []);
  $catRiesgoPuesto   = config('sat_nomina.riesgo_puesto', []);
  $catBanco          = config('sat_nomina.banco', []);
@endphp


@if($errors->any())
  <div class="mb-4 rounded-md bg-rose-50 px-3 py-2 text-sm text-rose-700">
    <ul class="list-disc ml-5">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block text-sm mb-1">Nombre *</label>
    <input type="text" name="nombre" value="{{ old('nombre', $empleado->nombre) }}" class="w-full rounded-md border p-2" required>
  </div>
  <div>
    <label class="block text-sm mb-1">RFC</label>
    <input type="text" name="rfc" value="{{ old('rfc', $empleado->rfc) }}" class="w-full rounded-md border p-2" maxlength="13">
  </div>
  <div>
    <label class="block text-sm mb-1">CURP</label>
    <input type="text" name="curp" value="{{ old('curp', $empleado->curp) }}" class="w-full rounded-md border p-2" maxlength="18">
  </div>
  <div>
    <label class="block text-sm mb-1">NSS</label>
    <input type="text" name="num_seguro_social" value="{{ old('num_seguro_social', $empleado->num_seguro_social) }}" class="w-full rounded-md border p-2" maxlength="11">
  </div>

  <div class="md:col-span-2"><hr class="my-2"></div>

  <div>
    <label class="block text-sm mb-1">Calle</label>
    <input type="text" name="calle" value="{{ old('calle', $empleado->calle) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Localidad</label>
    <input type="text" name="localidad" value="{{ old('localidad', $empleado->localidad) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">No. exterior</label>
    <input type="text" name="no_exterior" value="{{ old('no_exterior', $empleado->no_exterior) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">No. interior</label>
    <input type="text" name="no_interior" value="{{ old('no_interior', $empleado->no_interior) }}" class="w-full rounded-md border p-2">
  </div>
  <div class="md:col-span-2">
    <label class="block text-sm mb-1">Referencia</label>
    <input type="text" name="referencia" value="{{ old('referencia', $empleado->referencia) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Colonia</label>
    <input type="text" name="colonia" value="{{ old('colonia', $empleado->colonia) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Estado</label>
    <select name="estado" class="w-full rounded-md border p-2">
      <option value="">—</option>
      @foreach($estadosMx as $e)
        <option value="{{ $e }}" @selected(old('estado', $empleado->estado) === $e)>{{ $e }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label class="block text-sm mb-1">Municipio</label>
    <input type="text" name="municipio" value="{{ old('municipio', $empleado->municipio) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">País</label>
    <input type="text" name="pais" value="{{ old('pais', $empleado->pais ?? 'México') }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Código postal</label>
    <input type="text" name="codigo_postal" value="{{ old('codigo_postal', $empleado->codigo_postal) }}" class="w-full rounded-md border p-2">
  </div>

  <div class="md:col-span-2"><hr class="my-2"></div>

  <div>
    <label class="block text-sm mb-1">Teléfono</label>
    <input type="text" name="telefono" value="{{ old('telefono', $empleado->telefono) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Email</label>
    <input type="email" name="email" value="{{ old('email', $empleado->email) }}" class="w-full rounded-md border p-2">
  </div>

  <div class="md:col-span-2"><hr class="my-2"></div>

  <div>
    <label class="block text-sm mb-1">Registro patronal</label>
    <input type="text" name="registro_patronal" value="{{ old('registro_patronal', $empleado->registro_patronal) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Tipo contrato (SAT)</label>
    @if(count($catTipoContrato))
      <select name="tipo_contrato" class="w-full rounded-md border p-2">
        <option value="">—</option>
        @foreach($catTipoContrato as $clave => $nombre)
          <option value="{{ $clave }}" @selected(old('tipo_contrato', $empleado->tipo_contrato) === $clave)>
            {{ $clave }} — {{ $nombre }}
          </option>
        @endforeach
      </select>
    @else
      <input type="text" name="tipo_contrato" value="{{ old('tipo_contrato', $empleado->tipo_contrato) }}" class="w-full rounded-md border p-2" placeholder="Clave SAT (ej. 01)">
      <p class="text-xs text-gray-500 mt-1">Configura opciones en <code>config/sat_nomina.php</code></p>
    @endif
  </div>

  <div>
    <label class="block text-sm mb-1">No. empleado</label>
    <input type="text" name="numero_empleado" value="{{ old('numero_empleado', $empleado->numero_empleado) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Riesgo puesto (SAT)</label>
    @if(count($catRiesgoPuesto))
      <select name="riesgo_puesto" class="w-full rounded-md border p-2">
        <option value="">—</option>
        @foreach($catRiesgoPuesto as $clave => $nombre)
          <option value="{{ $clave }}" @selected(old('riesgo_puesto', $empleado->riesgo_puesto) === (string)$clave)>
            {{ $clave }} — {{ $nombre }}
          </option>
        @endforeach
      </select>
    @else
      <input type="text" name="riesgo_puesto" value="{{ old('riesgo_puesto', $empleado->riesgo_puesto) }}" class="w-full rounded-md border p-2" placeholder="1..5">
      <p class="text-xs text-gray-500 mt-1">Configura opciones en <code>config/sat_nomina.php</code></p>
    @endif
  </div>

  <div>
    <label class="block text-sm mb-1">Tipo jornada (SAT)</label>
    @if(count($catTipoJornada))
      <select name="tipo_jornada" class="w-full rounded-md border p-2">
        <option value="">—</option>
        @foreach($catTipoJornada as $clave => $nombre)
          <option value="{{ $clave }}" @selected(old('tipo_jornada', $empleado->tipo_jornada) === $clave)>
            {{ $clave }} — {{ $nombre }}
          </option>
        @endforeach
      </select>
    @else
      <input type="text" name="tipo_jornada" value="{{ old('tipo_jornada', $empleado->tipo_jornada) }}" class="w-full rounded-md border p-2" placeholder="Ej. 01, 02...">
      <p class="text-xs text-gray-500 mt-1">Configura opciones en <code>config/sat_nomina.php</code></p>
    @endif
  </div>

  <div>
    <label class="block text-sm mb-1">Puesto</label>
    <input type="text" name="puesto" value="{{ old('puesto', $empleado->puesto) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Fecha inicio laboral</label>
    <input type="date" name="fecha_inicio_laboral" value="{{ old('fecha_inicio_laboral', optional($empleado->fecha_inicio_laboral)->format('Y-m-d')) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Tipo régimen (SAT)</label>
    @if(count($catTipoRegimen))
      <select name="tipo_regimen" class="w-full rounded-md border p-2">
        <option value="">—</option>
        @foreach($catTipoRegimen as $clave => $nombre)
          <option value="{{ $clave }}" @selected(old('tipo_regimen', $empleado->tipo_regimen) === $clave)>
            {{ $clave }} — {{ $nombre }}
          </option>
        @endforeach
      </select>
    @else
      <input type="text" name="tipo_regimen" value="{{ old('tipo_regimen', $empleado->tipo_regimen) }}" class="w-full rounded-md border p-2" placeholder="Ej. 02 (Sueldos)">
      <p class="text-xs text-gray-500 mt-1">Configura opciones en <code>config/sat_nomina.php</code></p>
    @endif
  </div>

  <div>
    <label class="block text-sm mb-1">Salario</label>
    <input type="number" step="0.01" min="0" name="salario" value="{{ old('salario', $empleado->salario) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">Periodicidad pago (SAT)</label>
    @if(count($catPeriodicidad))
      <select name="periodicidad_pago" class="w-full rounded-md border p-2">
        <option value="">—</option>
        @foreach($catPeriodicidad as $clave => $nombre)
          <option value="{{ $clave }}" @selected(old('periodicidad_pago', $empleado->periodicidad_pago) === $clave)>
            {{ $clave }} — {{ $nombre }}
          </option>
        @endforeach
      </select>
    @else
      <input type="text" name="periodicidad_pago" value="{{ old('periodicidad_pago', $empleado->periodicidad_pago) }}" class="w-full rounded-md border p-2" placeholder="01=Diario, 04=Quincenal, 05=Mensual, ...">
      <p class="text-xs text-gray-500 mt-1">Configura opciones en <code>config/sat_nomina.php</code></p>
    @endif
  </div>

  <div>
    <label class="block text-sm mb-1">Salario diario integrado</label>
    <input type="number" step="0.01" min="0" name="salario_diario_integrado" value="{{ old('salario_diario_integrado', $empleado->salario_diario_integrado) }}" class="w-full rounded-md border p-2">
  </div>
  <div>
    <label class="block text-sm mb-1">CLABE</label>
    <input type="text" name="clabe" value="{{ old('clabe', $empleado->clabe) }}" class="w-full rounded-md border p-2" maxlength="18">
  </div>
  <div>
    <label class="block text-sm mb-1">Banco (SAT)</label>
    @if(count($catBanco))
      <select name="banco" class="w-full rounded-md border p-2">
        <option value="">—</option>
        @foreach($catBanco as $clave => $nombre)
          <option value="{{ $clave }}" @selected(old('banco', $empleado->banco) === $clave)>
            {{ $clave }} — {{ $nombre }}
          </option>
        @endforeach
      </select>
    @else
      <input type="text" name="banco" value="{{ old('banco', $empleado->banco) }}" class="w-full rounded-md border p-2" placeholder="Clave banco, ej. 012 (BBVA)">
      <p class="text-xs text-gray-500 mt-1">Configura opciones en <code>config/sat_nomina.php</code></p>
    @endif
  </div>

</div>
