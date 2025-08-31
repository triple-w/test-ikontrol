@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Nómina — {{ $nomina->uuid ?? 'Sin UUID' }}</h1>
    <div class="flex gap-2">
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('nominas.pdf', $nomina) }}">Descargar PDF</a>
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('nominas.xml', $nomina) }}">Descargar XML</a>
      <a class="px-3 py-2 rounded-md border text-sm" href="{{ route('nominas.index') }}">Regresar</a>
    </div>
  </div>

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
      <div class="p-4 rounded-md border">
        <div class="font-semibold mb-1">Empleado</div>
        <div>{{ $nomina->empleado->nombre ?? '—' }}</div>
        <div class="text-gray-500">{{ $nomina->empleado->rfc ?? '—' }}</div>
        <div class="mt-2"><strong>Fecha:</strong> {{ optional($nomina->fecha)->format('Y-m-d H:i') ?? '—' }}</div>
        <div><strong>Estatus:</strong> {{ ucfirst($nomina->estatus) }}</div>
      </div>

      <div class="p-4 rounded-md border md:col-span-2 space-y-6">
        {{-- Percepciones --}}
        <div>
          <div class="font-semibold mb-2">Percepciones</div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 border-b">
                  <th class="py-2 pr-3">Código</th>
                  <th class="py-2 pr-3">Clave</th>
                  <th class="py-2 pr-3">Concepto</th>
                  <th class="py-2 pr-3">Gravado</th>
                  <th class="py-2 pr-3">Exento</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @forelse($parsed['percepciones'] as $p)
                  <tr>
                    <td class="py-2 pr-3">{{ $p['codigo'] }}</td>
                    <td class="py-2 pr-3">{{ $p['clave'] }}</td>
                    <td class="py-2 pr-3">{{ $p['concepto'] }}</td>
                    <td class="py-2 pr-3">{{ number_format($p['gravado'],2) }}</td>
                    <td class="py-2 pr-3">{{ number_format($p['exento'],2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="py-3 text-center text-gray-500">Sin percepciones.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- Deducciones --}}
        <div>
          <div class="font-semibold mb-2">Deducciones</div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 border-b">
                  <th class="py-2 pr-3">Código</th>
                  <th class="py-2 pr-3">Clave</th>
                  <th class="py-2 pr-3">Concepto</th>
                  <th class="py-2 pr-3">Importe</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @forelse($parsed['deducciones'] as $d)
                  <tr>
                    <td class="py-2 pr-3">{{ $d['codigo'] }}</td>
                    <td class="py-2 pr-3">{{ $d['clave'] }}</td>
                    <td class="py-2 pr-3">{{ $d['concepto'] }}</td>
                    <td class="py-2 pr-3">{{ number_format($d['importe'],2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="py-3 text-center text-gray-500">Sin deducciones.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- Otros pagos --}}
        <div>
          <div class="font-semibold mb-2">Otros pagos</div>
          <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 border-b">
                  <th class="py-2 pr-3">Código</th>
                  <th class="py-2 pr-3">Clave</th>
                  <th class="py-2 pr-3">Concepto</th>
                  <th class="py-2 pr-3">Importe</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                @forelse($parsed['otros_pagos'] as $o)
                  <tr>
                    <td class="py-2 pr-3">{{ $o['codigo'] }}</td>
                    <td class="py-2 pr-3">{{ $o['clave'] }}</td>
                    <td class="py-2 pr-3">{{ $o['concepto'] }}</td>
                    <td class="py-2 pr-3">{{ number_format($o['importe'],2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="py-3 text-center text-gray-500">Sin otros pagos.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        {{-- Totales --}}
        <div class="bg-gray-50 dark:bg-gray-900/20 rounded-md p-4 text-sm">
          <div class="flex justify-between"><span>Percepciones (gravado)</span><span>{{ number_format($parsed['totales']['per_grav'],2) }}</span></div>
          <div class="flex justify-between"><span>Percepciones (exento)</span><span>{{ number_format($parsed['totales']['per_exen'],2) }}</span></div>
          <div class="flex justify-between"><span>Deducciones</span><span>{{ number_format($parsed['totales']['ded'],2) }}</span></div>
          <div class="flex justify-between"><span>Otros pagos</span><span>{{ number_format($parsed['totales']['otros'],2) }}</span></div>
          <div class="flex justify-between font-semibold text-base"><span>Neto</span><span>{{ number_format($parsed['totales']['neto'],2) }}</span></div>
        </div>
      </div>
    </div>

    <div class="text-xs text-gray-500">
      * Representación visual basada en el XML (CFDI Nómina 1.2). Los documentos oficiales están en “Descargar PDF/XML”.
    </div>
  </div>
</div>
@endsection
