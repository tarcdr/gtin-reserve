<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RmController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\BusinessSupplyController;
use App\Http\Controllers\BusinessSupplyBomController;
use App\Http\Controllers\FgBomController;
use App\Http\Controllers\RequestFormController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PackMaterialController;
use App\Http\Controllers\SemiFgLv1BomController;
use App\Http\Controllers\SemiFgLv2BomController;
use App\Http\Controllers\MaterialLevelController;
use App\Http\Controllers\UserManagementController;
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

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'view'])->name('dashboard');

    Route::get('/product/new', [ProductController::class, 'new'])->name('product.new');
    Route::patch('/product/create', [ProductController::class, 'create'])->name('product.create');
    Route::get('/product/search', [ProductController::class, 'search'])->name('product.search');
    Route::patch('/product/search', [ProductController::class, 'find'])->name('product.search');
    Route::get('/product/search/bom', [ProductController::class, 'searchBom'])->name('product.search.bom');
    Route::patch('/product/search/bom', [ProductController::class, 'findBom'])->name('product.search.bom');
    Route::get('/product/view', [ProductController::class, 'view'])->name('product.view');
    Route::get('/product/edit', [ProductController::class, 'edit'])->name('product.edit');
    Route::get('/product/sub-mattypes', [ProductController::class, 'subMattypes'])->name('product.sub-mattypes');
    Route::get('/product/generate-material-id', [ProductController::class, 'generateMaterialId'])->name('product.generate-material-id');
    Route::get('/product/generate-bom-id', [ProductController::class, 'generateBomId'])->name('product.generate-bom-id');
    Route::patch('/product/update', [ProductController::class, 'update'])->name('product.update');
    Route::delete('/product/delete', [ProductController::class, 'delete'])->name('product.delete');

    Route::get('/material-levels/raw', [MaterialLevelController::class, 'rawMaterial'])->name('material-levels.raw.new');
    Route::patch('/material-levels/raw', [MaterialLevelController::class, 'saveRaw'])->name('material-levels.raw.save');
    Route::get('/material-levels/raw/create-component', [MaterialLevelController::class, 'createComponentRaw'])->name('material-levels.raw.create-component');

    Route::get('/material-levels/semi-fg-lv2', [MaterialLevelController::class, 'semiFgLevel2'])->name('material-levels.semi-fg-lv2.new');
    Route::patch('/material-levels/semi-fg-lv2', [MaterialLevelController::class, 'saveSemiFgLevel2'])->name('material-levels.semi-fg-lv2.save');
    Route::patch('/material-levels/semi-fg-lv2/complete', [MaterialLevelController::class, 'completeSemiFgLevel2'])->name('material-levels.semi-fg-lv2.complete');
    Route::get('/material-levels/semi-fg-lv2/create-component', [MaterialLevelController::class, 'createComponentSemiFgLevel2'])->name('material-levels.semi-fg-lv2.create-component');
    Route::get('/material-levels/semi-fg-lv2/generate', [MaterialLevelController::class, 'generateSemiFgLevel2'])->name('material-levels.semi-fg-lv2.generate');

    Route::get('/material-levels/semi-fg-lv1', [MaterialLevelController::class, 'semiFgLevel1'])->name('material-levels.semi-fg-lv1.new');
    Route::patch('/material-levels/semi-fg-lv1', [MaterialLevelController::class, 'saveSemiFgLevel1'])->name('material-levels.semi-fg-lv1.save');
    Route::patch('/material-levels/semi-fg-lv1/complete', [MaterialLevelController::class, 'completeSemiFgLevel1'])->name('material-levels.semi-fg-lv1.complete');
    Route::get('/material-levels/semi-fg-lv1/create-component', [MaterialLevelController::class, 'createComponentSemiFgLevel1'])->name('material-levels.semi-fg-lv1.create-component');
    Route::get('/material-levels/semi-fg-lv1/generate', [MaterialLevelController::class, 'generateSemiFgLevel1'])->name('material-levels.semi-fg-lv1.generate');

    Route::get('/business-supply/new', [BusinessSupplyController::class, 'new'])->name('business-supply.new');
    Route::get('/business-supply/existing', [BusinessSupplyController::class, 'existing'])->name('business-supply.existing');
    Route::get('/business-supply/edit', [BusinessSupplyController::class, 'edit'])->name('business-supply.edit');
    Route::get('/business-supply/generate', [BusinessSupplyController::class, 'generate'])->name('business-supply.generate');
    Route::patch('/business-supply/save', [BusinessSupplyController::class, 'save'])->name('business-supply.save');
    Route::patch('/business-supply/existing', [BusinessSupplyController::class, 'updateExisting'])->name('business-supply.existing.update');
    Route::get('/business-supply/create-material-id', [BusinessSupplyController::class, 'createMaterialId'])->name('business-supply.create-material-id');
    Route::get('/business-supply/create-material-id/options', [BusinessSupplyController::class, 'materialIdOptions'])->name('business-supply.create-material-id.options');
    Route::get('/business-supply/create-material-id/detail', [BusinessSupplyController::class, 'materialIdDetail'])->name('business-supply.create-material-id.detail');
    Route::patch('/business-supply/create-material-id', [BusinessSupplyController::class, 'saveMaterialId'])->name('business-supply.create-material-id.save');
    Route::get('/business-supply/create-component', [BusinessSupplyController::class, 'createComponent'])->name('business-supply.create-component');
    Route::patch('/business-supply/create-component', [BusinessSupplyController::class, 'saveComponent'])->name('business-supply.create-component.save');
    Route::get('/business-supply/edit-component', [BusinessSupplyController::class, 'editComponent'])->name('business-supply.edit-component');
    Route::patch('/business-supply/edit-component', [BusinessSupplyController::class, 'saveComponent'])->name('business-supply.edit-component.save');
    Route::get('/business-supply/generate-component-id', [BusinessSupplyController::class, 'generateComponentId'])->name('business-supply.generate-component-id');
    Route::get('/rm/material_create', [RmController::class, 'view'])->name('rm.material_create');
    Route::get('/rm/component_request', [RmController::class, 'viewComponentRequest'])->name('rm.component_request');
    Route::get('/rm/report/{tab?}', [RmController::class, 'report'])->name('rm.report');
    Route::patch('/rm/confirm', [RmController::class, 'update'])->name('rm.confirm');
    Route::delete('/rm/delete', [RmController::class, 'delete'])->name('rm.delete');
    Route::get('/rm/export', [RmController::class, 'export'])->name('rm.export');
    Route::middleware('admin')->post('/rm/export-to-sap', [RmController::class, 'exportToSap'])->name('rm.export-sap');

    Route::get('/packmaterial/fg-bom', [FgBomController::class, 'new'])->name('packmaterial.fg-bom.new');
    Route::get('/packmaterial/semi-fg-lv1-bom', [SemiFgLv1BomController::class, 'new'])->name('packmaterial.semi-fg-lv1-bom.new');
    Route::get('/packmaterial/semi-fg-lv2-bom', [SemiFgLv2BomController::class, 'new'])->name('packmaterial.semi-fg-lv2-bom.new');
    Route::patch('/packmaterial/fg-bom', [FgBomController::class, 'save'])->name('packmaterial.fg-bom.save');
    Route::patch('/packmaterial/semi-fg-lv1-bom', [SemiFgLv1BomController::class, 'save'])->name('packmaterial.semi-fg-lv1-bom.save');
    Route::patch('/packmaterial/semi-fg-lv2-bom', [SemiFgLv2BomController::class, 'save'])->name('packmaterial.semi-fg-lv2-bom.save');
    Route::get('/packmaterial/new', [BusinessSupplyBomController::class, 'new'])->name('packmaterial.new');
    Route::post('/packmaterial/new', [BusinessSupplyBomController::class, 'callNew'])->name('packmaterial.new');
    Route::get('/packmaterial/product-categories', [PackMaterialController::class, 'productCategories'])->name('packmaterial.product-categories');
    Route::get('/packmaterial/generate-component-id', [PackMaterialController::class, 'generateComponentId'])->name('packmaterial.generate-component-id');
    Route::patch('/packmaterial/create', [BusinessSupplyBomController::class, 'save'])->name('packmaterial.create');
    Route::patch('/packmaterial/update', [BusinessSupplyBomController::class, 'save'])->name('packmaterial.update');

    Route::get('/bom/create', [BomController::class, 'create'])->name('bom.create');
    Route::patch('/bom/create', [BomController::class, 'process'])->name('bom.create');
    Route::get('/bom/new', [BomController::class, 'view'])->name('bom.new');
    Route::get('/bom/{id}', [BomController::class, 'exists'])->name('bom.exists');

    Route::get('/bns/create', [BusinessSupplyController::class, 'create'])->name('bns.create');

    Route::get('/material/request', [MaterialController::class, 'view'])->name('material.request');
    Route::patch('/material/request', [MaterialController::class, 'update'])->name('material.update');
    Route::get('/material/report', [MaterialController::class, 'report'])->name('material.report');
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

    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', [UserManagementController::class, 'index'])->name('admin.users.index');
        Route::post('/admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::patch('/admin/users/{user_login}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::patch('/admin/users/{user_login}/password', [UserManagementController::class, 'updatePassword'])->name('admin.users.password');
        Route::delete('/admin/users/{user_login}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
    });
});

require __DIR__.'/auth.php';
