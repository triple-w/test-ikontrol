<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Configuracion\RfcController;
use App\Http\Controllers\Catalogos\ClientesController;
use App\Http\Controllers\Catalogos\ProductosController;
use App\Http\Controllers\Catalogos\CatalogSearchController;
use App\Http\Controllers\Catalogos\FoliosController;
use App\Http\Controllers\Catalogos\EmpleadosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Facturacion\FacturasHistorialController;
use App\Http\Controllers\Facturacion\NominasHistorialController;
use App\Http\Controllers\Facturacion\ComplementosHistorialController;
use App\Http\Controllers\Facturacion\FacturasController;
use App\Http\Controllers\Facturacion\FacturaUiController;

use App\Http\Controllers\Configuracion\SellosController;
use App\Http\Controllers\Configuracion\PerfilRfcController;

use App\Http\Controllers\Admin\TimbresController;
use App\Http\Controllers\Admin\PacPlaygroundController;

// Home -> Dashboard
Route::middleware(['auth'])->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// (Opcional) si quieres que / vaya directo al Dashboard:
Route::redirect('/', '/dashboard');

// -------- Cambiar RFC activo (lo usa tu dropdown del header)
Route::post('/cambiar-rfc', [RfcController::class, 'cambiar'])->name('rfc.cambiar');

Route::middleware(['auth'])->group(function () {

    // --- API auxiliares (SE MANTIENEN para compatibilidad) ---
    Route::get('/api/series/next',        [FoliosController::class,    'apiNext'])->name('series.next');
    Route::get('/api/productos/buscar',   [ProductosController::class, 'buscar'])->name('productos.buscar');
    Route::get('/api/sat/clave-prod-serv',[ProductosController::class, 'buscarClaveProdServ'])->name('sat.clave_prod_serv');
    Route::get('/api/sat/clave-unidad',   [ProductosController::class, 'buscarClaveUnidad'])->name('sat.clave_unidad');

    // Quick update de cliente desde el modal lateral en create de facturas
    Route::put('/catalogos/clientes/{cliente}/quick-update', [ClientesController::class, 'quickUpdate'])->name('clientes.quickUpdate');

    // ======================== FACTURAS - UI NUEVA ========================
    // Pantalla de creación
    Route::get('/facturacion/facturas/crear', [FacturaUiController::class, 'create'])->name('facturas.create');

    // Preview (validación obligatoria)
    Route::post('/facturacion/facturas/preview', [FacturaUiController::class, 'preview'])->name('facturas.preview');

    // Guardado (borrador / persistencia que definas en el controlador)
    Route::post('/facturacion/facturas', [FacturaUiController::class, 'store'])->name('facturas.store');

    // Timbrado desde el preview (añadido; no existía conflicto previo)
    Route::post('/facturacion/facturas/timbrar', [FacturaUiController::class, 'timbrar'])->name('facturas.timbrar');
});

// ======================== ÁREA AUTENTICADA ========================
Route::middleware(['auth'])->group(function () {

    // ======================== CATÁLOGOS ========================
    Route::prefix('catalogos')->group(function () {
        // Clientes
        Route::get('clientes',                 [ClientesController::class, 'index'])->name('clientes.index');
        Route::get('clientes/create',          [ClientesController::class, 'create'])->name('clientes.create');
        Route::post('clientes',                [ClientesController::class, 'store'])->name('clientes.store');
        Route::get('clientes/{cliente}/edit',  [ClientesController::class, 'edit'])->name('clientes.edit');
        Route::put('clientes/{cliente}',       [ClientesController::class, 'update'])->name('clientes.update');
        Route::delete('clientes/{cliente}',    [ClientesController::class, 'destroy'])->name('clientes.destroy');

        // Productos
        Route::get('productos',                 [ProductosController::class, 'index'])->name('productos.index');
        Route::get('productos/create',          [ProductosController::class, 'create'])->name('productos.create');
        Route::post('productos',                [ProductosController::class, 'store'])->name('productos.store');
        Route::get('productos/{producto}/edit', [ProductosController::class, 'edit'])->name('productos.edit');
        Route::put('productos/{producto}',      [ProductosController::class, 'update'])->name('productos.update');
        Route::delete('productos/{producto}',   [ProductosController::class, 'destroy'])->name('productos.destroy');

        // Folios
        Route::get('folios',                 [FoliosController::class, 'index'])->name('folios.index');
        Route::get('folios/create',          [FoliosController::class, 'create'])->name('folios.create');
        Route::post('folios',                [FoliosController::class, 'store'])->name('folios.store');
        Route::get('folios/{folio}/edit',    [FoliosController::class, 'edit'])->name('folios.edit');
        Route::put('folios/{folio}',         [FoliosController::class, 'update'])->name('folios.update');
        Route::delete('folios/{folio}',      [FoliosController::class, 'destroy'])->name('folios.destroy');

        // Empleados
        Route::get('empleados',                   [EmpleadosController::class, 'index'])->name('empleados.index');
        Route::get('empleados/crear',             [EmpleadosController::class, 'create'])->name('empleados.create');
        Route::post('empleados',                  [EmpleadosController::class, 'store'])->name('empleados.store');
        Route::get('empleados/{empleado}/editar', [EmpleadosController::class, 'edit'])->name('empleados.edit');
        Route::put('empleados/{empleado}',        [EmpleadosController::class, 'update'])->name('empleados.update');
        Route::delete('empleados/{empleado}',     [EmpleadosController::class, 'destroy'])->name('empleados.destroy');

        // Búsquedas catálogos SAT (para selects/autocomplete)
        Route::get('search/prodserv', [CatalogSearchController::class, 'prodServ'])->name('catalogos.search.prodserv');
        Route::get('search/unidades', [CatalogSearchController::class, 'unidades'])->name('catalogos.search.unidades');
    });

    // ======================== FACTURACIÓN (historiales) ========================
    Route::prefix('facturacion')->group(function () {
        // Historial Facturas
        Route::get('facturas',                   [FacturasHistorialController::class, 'index'])->name('facturas.index');
        Route::get('facturas/{factura}',         [FacturasHistorialController::class, 'show'])->name('facturas.show');
        Route::get('facturas/{factura}/pdf',     [FacturasHistorialController::class, 'descargarPdf'])->name('facturas.pdf');
        Route::get('facturas/{factura}/xml',     [FacturasHistorialController::class, 'descargarXml'])->name('facturas.xml');
        Route::post('facturas/{factura}/email',  [FacturasHistorialController::class, 'enviarEmail'])->name('facturas.email');

        // Historial Complementos
        Route::get('complementos',                   [ComplementosHistorialController::class, 'index'])->name('complementos.index');
        Route::get('complementos/{complemento}',     [ComplementosHistorialController::class, 'show'])->name('complementos.show');
        Route::get('complementos/{complemento}/pdf', [ComplementosHistorialController::class, 'descargarPdf'])->name('complementos.pdf');
        Route::get('complementos/{complemento}/xml', [ComplementosHistorialController::class, 'descargarXml'])->name('complementos.xml');
        Route::post('complementos/{complemento}/email', [ComplementosHistorialController::class, 'enviarEmail'])->name('complementos.email');

        // Historial Nóminas
        Route::get('nominas',                  [NominasHistorialController::class,'index'])->name('nominas.index');
        Route::get('nominas/{nomina}',         [NominasHistorialController::class,'show'])->name('nominas.show');
        Route::get('nominas/{nomina}/pdf',     [NominasHistorialController::class,'descargarPdf'])->name('nominas.pdf');
        Route::get('nominas/{nomina}/xml',     [NominasHistorialController::class,'descargarXml'])->name('nominas.xml');

        // Placeholders de creación (para el botón “Generar” del header)
        Route::get('complementos/crear', fn () => view('wip', ['titulo' => 'Nuevo Complemento']))->name('complementos.create');
    });

    // Nóminas crear (ruta fuera del prefijo anterior si así lo prefieres)
    Route::get('/nominas/crear', fn () => view('wip', ['titulo' => 'Nueva Nómina']))->name('nominas.create');

    // ======================== CONFIGURACIÓN ========================
    Route::prefix('configuracion')->group(function () {
        // Perfil del RFC
        Route::get('/perfil', [PerfilRfcController::class, 'edit'])->name('perfil.edit');
        Route::put('/perfil', [PerfilRfcController::class, 'update'])->name('perfil.update');

        // Sellos (CSD)
        Route::get('/sellos',                 [SellosController::class, 'index'])->name('sellos.index');
        Route::post('/sellos',                [SellosController::class, 'store'])->name('sellos.store');
        Route::post('/sellos/{csd}/activar',  [SellosController::class, 'activar'])->name('sellos.activar');
        Route::delete('/sellos/{csd}',        [SellosController::class, 'destroy'])->name('sellos.destroy');
    });

    // ======================== ADMIN ========================
    Route::prefix('admin')->group(function () {
        // Timbres
        Route::get('/timbres',         [TimbresController::class, 'index'])->name('admin.timbres.index');
        Route::post('/timbres',        [TimbresController::class, 'store'])->name('admin.timbres.store');
        Route::get('/timbres/history', [TimbresController::class, 'history'])->name('admin.timbres.history');

        // PAC playground (si lo usas)
        Route::get('/pac',        [PacPlaygroundController::class, 'index'])->name('admin.pac.index')->middleware('can:admin-only');
        Route::post('/pac/timbrar',[PacPlaygroundController::class, 'timbrar'])->name('admin.pac.timbrar')->middleware('can:admin-only');
    });
});

// ====== Vista WIP genérica ======
Route::view('/wip', 'wip')->name('wip');
