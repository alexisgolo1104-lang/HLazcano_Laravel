<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PdfController;
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
    Route::get('/registro', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registro', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
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
    Route::post('/compras', [ComprasController::class, 'store'])->name('compras.store');

    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes');
    Route::post('/clientes', [ClientesController::class, 'store'])->name('clientes.store');

    Route::get('/alertas', [PanelController::class, 'alertas'])->name('alertas');
    Route::get('/localizador', [PanelController::class, 'localizador'])->name('localizador');
    Route::get('/reportes', [PanelController::class, 'reportes'])->name('reportes');
    Route::get('/usuarios', [PanelController::class, 'usuarios'])->name('usuarios');
    Route::get('/catalogos', [PanelController::class, 'catalogos'])->name('catalogos');
    Route::get('/respaldos', [RespaldoController::class, 'index'])->name('respaldos');
    Route::post('/respaldos/exportar', [RespaldoController::class, 'exportar'])->name('respaldos.exportar');
    Route::post('/respaldos/importar', [RespaldoController::class, 'importar'])->name('respaldos.importar');
});
