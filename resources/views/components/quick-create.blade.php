<div x-data="{ open:false }" @keydown.escape.window="open=false" class="relative">
  <button
    type="button"
    @click="open = !open"
    @mouseenter="open = true"
    class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40"
    aria-haspopup="true"
    :aria-expanded="open"
  >
    <!-- icono + -->
    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
      <path stroke-width="2" d="M12 5v14M5 12h14"/>
    </svg>
    Generar
    <svg class="w-3 h-3 ml-1 opacity-70" viewBox="0 0 12 12" fill="currentColor">
      <path d="M5.9 8.5 2.5 4.5h6.8L5.9 8.5z"/>
    </svg>
  </button>

  <!-- dropdown -->
  <div
    x-cloak
    x-show="open"
    @mouseleave="open=false"
    @click.outside="open=false"
    x-transition:enter="transition ease-out duration-200 transform"
    x-transition:enter-start="opacity-0 -translate-y-1"
    x-transition:enter-end="opacity-100 translate-y-0"
    class="absolute right-0 mt-2 w-52 rounded-md border bg-white p-2 shadow-lg dark:border-gray-700/60 dark:bg-gray-800 z-50"
  >
    <a href="{{ route('facturas.create') }}"
       class="flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/60">
      <span class="inline-block w-4 h-4">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h4"/></svg>
      </span>
      Nueva Factura
    </a>
    <a href="{{ route('complementos.create') }}"
       class="flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/60">
      <span class="inline-block w-4 h-4">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M12 20l9-16H3l9 16z"/></svg>
      </span>
      Nuevo Complemento
    </a>
    <a href="{{ route('nominas.create') }}"
       class="flex items-center gap-2 rounded-md px-2 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/60">
      <span class="inline-block w-4 h-4">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"/></svg>
      </span>
      Nueva Nómina
    </a>
  </div>
</div>
