<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CriticalSiteController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\GeographyController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\IncidentCatalogController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\OperationalDashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => redirect()->route(auth()->check() ? 'operations.dashboard' : 'login'));

Route::middleware(['auth', EnsureActiveUser::class, 'auth.session'])->group(function () {
    Route::get('/operaciones', OperationalDashboardController::class)->name('operations.dashboard');
    Route::get('/mapa-incidencias', [IncidentController::class, 'map'])->name('incidents.map');
    Route::get('/mapa-incidencias/datos', [IncidentController::class, 'mapData'])->name('incidents.map-data');
    Route::get('/geografia/buscar', [GeographyController::class, 'search'])->middleware('throttle:30,1')->name('geography.search');
    Route::get('/incidencias', [IncidentController::class, 'index'])->name('incidents.index');
    Route::get('/incidencias/nueva', [IncidentController::class, 'create'])->name('incidents.create');
    Route::post('/incidencias', [IncidentController::class, 'store'])->name('incidents.store');
    Route::get('/incidencias/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
    Route::get('/incidencias/{incident}/editar', [IncidentController::class, 'edit'])->name('incidents.edit');
    Route::put('/incidencias/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
    Route::post('/incidencias/{incident}/seguimiento', [IncidentController::class, 'transition'])->name('incidents.transition');
    Route::post('/incidencias/{incident}/fotografias', [IncidentController::class, 'attach'])->name('incidents.attach');
    Route::get('/evidencias-incidencias/{evidence}', [IncidentController::class, 'photo'])->name('incidents.photo');
    Route::get('/puntos-criticos', [CriticalSiteController::class, 'index'])->name('sites.index');
    Route::get('/puntos-criticos/{site}', [CriticalSiteController::class, 'show'])->name('sites.show');
    Route::post('/incidencias/{incident}/sitio-critico', [CriticalSiteController::class, 'store'])->name('sites.store');
    Route::put('/puntos-criticos/{site}', [CriticalSiteController::class, 'update'])->name('sites.update');
    Route::get('/panel', fn () => redirect()->route('operations.dashboard'))->name('dashboard');
    Route::get('/registros', [EvidenceController::class, 'records'])->name('records.index');
    Route::get('/registros/{record}', [EvidenceController::class, 'record'])->name('records.show');
    Route::get('/fuentes', [EvidenceController::class, 'sources'])->name('sources.index');
    Route::get('/archivos/{file}', [EvidenceController::class, 'download'])->name('files.download');
    Route::get('/rutas', [EvidenceController::class, 'routes'])->name('routes.index');
    Route::get('/reportes/{format}', [ReportController::class, 'export'])->whereIn('format', ['pdf', 'xlsx'])->name('reports.export');
    Route::get('/perfil', [AccountController::class, 'profile'])->name('profile');
    Route::put('/perfil', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::middleware('can:import')->group(function () {
        Route::get('/importaciones', [ImportController::class, 'index'])->name('imports.index');
        Route::post('/importaciones', [ImportController::class, 'store'])->name('imports.store');
        Route::get('/importaciones/{batch}', [ImportController::class, 'show'])->name('imports.show');
        Route::post('/importaciones/{batch}/confirmar', [ImportController::class, 'confirm'])->name('imports.confirm');
    });
    Route::middleware('can:administer')->group(function () {
        Route::get('/configuracion/incidencias', [IncidentCatalogController::class, 'index'])->name('incident-catalogs.index');
        Route::post('/configuracion/incidencias', [IncidentCatalogController::class, 'store'])->name('incident-catalogs.store');
        Route::put('/configuracion/incidencias/{catalog}/{id}', [IncidentCatalogController::class, 'update'])->name('incident-catalogs.update');
        Route::put('/configuracion/flujo-incidencias', [IncidentCatalogController::class, 'settings'])->name('incident-catalogs.settings');
        Route::get('/usuarios', [AccountController::class, 'users'])->name('users.index');
        Route::post('/usuarios', [AccountController::class, 'store'])->name('users.store');
        Route::get('/usuarios/{user}/editar', [AccountController::class, 'edit'])->name('users.edit');
        Route::put('/usuarios/{user}', [AccountController::class, 'update'])->name('users.update');
        Route::get('/auditoria', [AccountController::class, 'audit'])->name('audit');
    });
});
