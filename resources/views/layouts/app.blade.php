<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles        

        <script>
            if (localStorage.getItem('dark-mode') === 'false' || !('dark-mode' in localStorage)) {
                document.querySelector('html').classList.remove('dark');
                document.querySelector('html').style.colorScheme = 'light';
            } else {
                document.querySelector('html').classList.add('dark');
                document.querySelector('html').style.colorScheme = 'dark';
            }
        </script>
    </head>
    <body
        class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400"
        :class="{ 'sidebar-expanded': sidebarExpanded }"
        x-data="{ sidebarOpen: false, sidebarExpanded: localStorage.getItem('sidebar-expanded') == 'true' }"
        x-init="$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value))"    
    >

        <script>
            if (localStorage.getItem('sidebar-expanded') == 'true') {
                document.querySelector('body').classList.add('sidebar-expanded');
            } else {
                document.querySelector('body').classList.remove('sidebar-expanded');
            }
        </script>

        @php
            // Valores por defecto si no te pasan nada desde la vista hija
            $sidebarVariant = $sidebarVariant ?? null;
            $headerVariant  = $headerVariant  ?? null;
            $background     = $background     ?? null;
        @endphp

        @if(isset($banner_timbres))
        <div class="bg-amber-50 text-amber-800 text-sm px-4 py-2">
            Atención: te quedan <strong>{{ $banner_timbres }}</strong> timbres disponibles para el RFC activo.
        </div>
        @endif


        <!-- Page wrapper -->
           </script>



       {{-- ...tu head y header... --}}

@php
    $sidebarVariant = $sidebarVariant ?? null;
    $headerVariant  = $headerVariant  ?? null;
    $background     = $background     ?? null;
@endphp

<!-- Page wrapper -->
<div class="flex h-[100dvh] overflow-hidden">

    <x-app.sidebar :variant="$sidebarVariant" />

    <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden {{ $background ?? '' }}" x-ref="contentarea">

        <x-app.header :variant="$headerVariant" />

        <main class="grow">
            @yield('content')   {{-- <— antes tenías {{ $slot }} --}}
        </main>

    </div>

</div>
@vite(['resources/css/app.css','resources/js/app.js'])
@stack('scripts')
@livewireScriptConfig
</body>
</html>