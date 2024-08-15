<?php

use Illuminate\Support\Facades\Route;
use Hitexis\Admin\Http\Controllers\Catalog\ProductController;
use Hitexis\Markup\Http\Controllers\MarkupController;
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(ProductController::class)->prefix('products')->group(function () {
        // Route::get('/admin/catalog/products/getdata', 'getData')->name('hitexis-admin.catalog.products.markup-manager.getdata');
        // Route::get('admin/catalog/products/edit/{id}', 'edit')->name('hitexis-admin.catalog.products.markup-manager.edit');
        // Route::get('/admin/catalog/products/store', 'store')->name('hitexis-admin.catalog.products.markup-manager.store');

    
        Route::get('admin/catalog/products/edit/{id}', [ProductController::class, 'edit'])->defaults('_config', [
            'view' => 'hitexis-admin::catalog.products.edit',
        ])->name('hitexis-admin.catalog.products.markup-manager.edit');
    
        Route::put('admin/catalog/products/edit/{id}', [ProductController::class, 'update'])->defaults('_config', [
            'redirect' => 'hitexis-admin.catalog.products.index',
        ])->name('hitexis-admin.catalog.products.markup-manager.update');
    
        Route::post('admin/catalog/products/store', [MarkupController::class, 'update'])->defaults('_config', [
            'redirect' => 'hitexis-admin.catalog.products.index',
        ])->name('hitexis-admin.catalog.products.markup-manager.store');
    });

    // Route::post('admin/catalog/products/create', [MarkupController::class, 'create'])->defaults('_config', [
    //     'redirect' => 'hitexis-admin.catalog.products.index',
    // ])->name('hitexis-admin.catalog.products.markup-manager.create');
});