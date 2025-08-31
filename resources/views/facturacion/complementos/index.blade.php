@extends('layouts.app')

@section('content')
<div class="p-4 sm:p-6"
     x-data="{
        modal:false,
        compId:null,
        toEmail:'',
        ccEmail:'',
        openEmail(id, defEmail){
            this.compId = id;
            this.toEmail = defEmail || '';
            this.ccEmail = '';
            this.modal = true;
            $nextTick(()=>{ const i=$refs.toInput; if(i) i.focus(); });
        },
        close(){ this.modal=false; this.compId=null; this.toEmail=''; this.ccEmail=''; },
        actionUrl(){ return `{{ url('/facturacion/complementos') }}/${this.compId}/email`; }
     }">

  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">Complementos de pago (RFC: {{ session('rfc_seleccionado') ?? '—' }})</h1>
    <form method="GET" class="flex gap-2">
      <select name="estatus" class="rounded-md border p-2 text-sm">
        <option value="">Estatus</option>
        <option value="borrador"  @selected(request('estatus')==='borrador')>Borrador</option>
        <option value="timbrado"  @selected(request('estatus')==='timbrado')>Timbrado</option>
        <option value="cancelado" @selected(request('estatus')==='cancelado')>Cancelado</option>
      </select>
      <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="UUID / RFC / Razón social" class="rounded-md border p-2 text-sm">
    </form>
  </div>

  @if(session('ok'))
    <div class="mb-3 rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('ok') }}</div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="text-left text-gray-500 border-b">
            <th class="py-2 pr-4">Monto pagado</th>
            <th class="py-2 pr-4">Receptor</th>
            <th class="py-2 pr-4">Fecha</th>
            <th class="py-2 pr-4">Estatus</th>
            <th class="py-2 pr-4">UUID</th>
            <th class="py-2 pr-4"></th>
          </tr>
        </thead>
        <tbody class="divide-y">
          @forelse($items as $c)
            @php $emailDef = $emailsPorRfc[$c->rfc] ?? ''; @endphp
            <tr class="hover:bg-gray-50">
              <td class="py-2 pr-4">{{ $c->pagos_sum_monto_pago !== null ? number_format($c->pagos_sum_monto_pago, 2) : '—' }}</td>
              <td class="py-2 pr-4">{{ $c->razon_social }} <span class="text-gray-500">({{ $c->rfc }})</span></td>
              <td class="py-2 pr-4">{{ optional($c->fecha)->format('Y-m-d H:i') ?? '—' }}</td>
              <td class="py-2 pr-4">{{ $c->estatus }}</td>
              <td class="py-2 pr-4 font-mono">{{ $c->uuid ?? '—' }}</td>
              <td class="py-2 pr-4">
                <div class="flex gap-3">
                  <a href="{{ route('complementos.show', $c) }}" class="text-violet-600 hover:underline text-sm">Ver</a>
                  <a href="{{ route('complementos.pdf', $c) }}"  class="text-violet-600 hover:underline text-sm">PDF</a>
                  <a href="{{ route('complementos.xml', $c) }}"  class="text-violet-600 hover:underline text-sm">XML</a>
                  <button type="button"
                          class="text-violet-600 hover:underline text-sm"
                          @click="openEmail({{ $c->id }}, '{{ e($emailDef) }}')">
                    Enviar
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="py-6 text-center text-gray-500">Sin complementos.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
  </div>

  {{-- Modal Enviar --}}
  <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="close()"></div>
    <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-xl shadow p-6">
      <h2 class="text-lg font-semibold mb-4">Enviar complemento por correo</h2>
      <form method="POST" :action="actionUrl()">
        @csrf
        <div class="space-y-3">
          <div>
            <label class="block text-sm mb-1">Para</label>
            <input x-ref="toInput" x-model="toEmail" type="email" name="to" class="w-full rounded-md border p-2" required>
          </div>
          <div>
            <label class="block text-sm mb-1">CC (opcional)</label>
            <input x-model="ccEmail" type="email" name="cc" class="w-full rounded-md border p-2">
          </div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded-md border text-sm" @click="close()">Cancelar</button>
          <button type="submit" class="px-3 py-2 rounded-md bg-violet-600 text-white text-sm">Enviar</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
