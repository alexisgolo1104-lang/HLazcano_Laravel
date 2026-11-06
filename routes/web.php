<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\CatalogosController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EgresosController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProveedoresController;
use App\Http\Controllers\RespaldoController;
use App\Http\Controllers\VentasController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/recuperar-contrasena', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/recuperar-contrasena', [AuthController::class, 'sendPasswordResetLink'])->name('password.email');
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->name('register.store');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'password.change'])->group(function () {
    Route::get('/cambiar-contrasena', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::put('/cambiar-contrasena', [AuthController::class, 'changePassword'])->name('password.change.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario');
    Route::get('/inventario/crear', [InventarioController::class, 'create'])->name('inventario.create');
    Route::post('/inventario', [InventarioController::class, 'store'])->name('inventario.store');
    Route::get('/inventario/{id}/editar', [InventarioController::class, 'edit'])->name('inventario.edit');
    Route::put('/inventario/{id}', [InventarioController::class, 'update'])->name('inventario.update');
    Route::delete('/inventario/{id}', [InventarioController::class, 'destroy'])->name('inventario.destroy');

    Route::get('/ventas', [VentasController::class, 'index'])->name('ventas');
    Route::post('/ventas', [VentasController::class, 'store'])->name('ventas.store');
    Route::post('/ventas/{id}/cancelar', [VentasController::class, 'cancelar'])->name('ventas.cancelar');
    Route::get('/ventas/{id}/ticket', [PdfController::class, 'ticket'])->name('ventas.ticket');

    Route::get('/compras', [ComprasController::class, 'index'])->name('compras');
    Route::get('/compras/productos/{id}/historial', [ComprasController::class, 'historialProducto'])
        ->name('compras.historial-producto');
    Route::get('/compras/{id}/ticket', [ComprasController::class, 'ticket'])->name('compras.ticket');
    Route::post('/compras', [ComprasController::class, 'store'])->name('compras.store');

    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes');
    Route::post('/clientes', [ClientesController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/{id}/editar', [ClientesController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{id}', [ClientesController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{id}', [ClientesController::class, 'destroy'])->name('clientes.destroy');

    Route::get('/alertas', [PanelController::class, 'alertas'])->name('alertas');
    Route::post('/alertas/atender-todas', [PanelController::class, 'atenderTodasAlertas'])->name('alertas.atender-todas');
    Route::get('/localizador', [PanelController::class, 'localizador'])->name('localizador');
    Route::get('/reportes', [PanelController::class, 'reportes'])->name('reportes');
    Route::get('/usuarios', [PanelController::class, 'usuarios'])->name('usuarios');
    Route::get('/usuarios/{id}/editar', [PanelController::class, 'editarUsuario'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [PanelController::class, 'actualizarUsuario'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [PanelController::class, 'desactivarUsuario'])->name('usuarios.destroy');
    Route::get('/equipo/crear', [PanelController::class, 'crearMiembroEquipo'])->name('equipo.create');
    Route::get('/equipo/{id}/editar', [PanelController::class, 'editarMiembroEquipo'])->name('equipo.edit');
    Route::post('/equipo', [PanelController::class, 'storeMiembroEquipo'])->name('equipo.store');
    Route::put('/equipo/{id}', [PanelController::class, 'actualizarMiembroEquipo'])->name('equipo.update');
    Route::delete('/equipo/{id}', [PanelController::class, 'desactivarMiembroEquipo'])->name('equipo.destroy');
    Route::post('/usuarios/{id}/aprobar-administrador', [PanelController::class, 'aprobarAdministrador'])
        ->name('usuarios.aprobar-administrador');
    Route::get('/catalogos', [CatalogosController::class, 'index'])->name('catalogos');
    Route::get('/catalogos/{seccion}/{id}/editar', [CatalogosController::class, 'edit'])->name('catalogos.edit');
    Route::post('/catalogos/{seccion}', [CatalogosController::class, 'store'])->name('catalogos.store');
    Route::put('/catalogos/{seccion}/{id}', [CatalogosController::class, 'update'])->name('catalogos.update');
    Route::patch('/catalogos/productos/{id}/estado', [CatalogosController::class, 'deactivateProduct'])->name('catalogos.productos.estado');
    Route::get('/proveedores', [ProveedoresController::class, 'index'])->name('proveedores');
    Route::get('/proveedores/{id}/editar', [ProveedoresController::class, 'edit'])->name('proveedores.edit');
    Route::post('/proveedores', [ProveedoresController::class, 'store'])->name('proveedores.store');
    Route::put('/proveedores/{id}', [ProveedoresController::class, 'update'])->name('proveedores.update');
    Route::patch('/proveedores/{id}/estado', [ProveedoresController::class, 'toggleActive'])->name('proveedores.estado');
    Route::get('/egresos', [EgresosController::class, 'index'])->name('egresos');
    Route::post('/egresos', [EgresosController::class, 'store'])->name('egresos.store');
    Route::get('/respaldos', [RespaldoController::class, 'index'])->name('respaldos');
    Route::post('/respaldos/exportar', [RespaldoController::class, 'exportar'])->name('respaldos.exportar');
    Route::post('/respaldos/importar', [RespaldoController::class, 'importar'])->name('respaldos.importar');
});
