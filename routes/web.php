<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RmController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\RequestFormController;
use App\Http\Controllers\MaterialController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'view'])->name('dashboard');
Route::get('/product/new', [ProductController::class, 'view'])->name('product.new');
Route::get('/rm/material_create', [RmController::class, 'view'])->name('rm.material_create');
Route::get('/rm/component_request', [RmController::class, 'viewComponentRequest'])->name('rm.component_request');
Route::get('/rm/report', [RmController::class, 'report'])->name('rm.report');

Route::get('/bom/new', [BomController::class, 'view'])->name('bom.new');
Route::get('/bom/{id}', [BomController::class, 'exists'])->name('bom.exists');

Route::get('/material/request', [MaterialController::class, 'view'])->name('material.request');
Route::patch('/material/request', [MaterialController::class, 'update'])->name('material.update');
Route::get('/material/report', [MaterialController::class, 'report'])->name('material.report');
Route::middleware('auth')->group(function () {
    Route::patch('/material/report', [MaterialController::class, 'confirm'])->name('material.confirm');
    Route::patch('/material/search', [MaterialController::class, 'search'])->name('material.search');
    Route::get('/material/export', [MaterialController::class, 'export'])->name('material.export');

    Route::get('/report', [RequestFormController::class, 'report'])->name('report');
    Route::patch('/report', [RequestFormController::class, 'confirm'])->name('report.confirm');
    Route::patch('/request/report', [RequestFormController::class, 'search'])->name('request.report');
    Route::get('/export', [RequestFormController::class, 'export'])->name('export');
    Route::get('/request', [RequestFormController::class, 'view'])->name('request');
    Route::patch('/request', [RequestFormController::class, 'update'])->name('request.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
