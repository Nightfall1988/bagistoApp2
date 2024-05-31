<?php

use Illuminate\Support\Facades\Route;
use Hitexis\Wholesale\Http\Controllers\WholesaleController;
/**
 * Wholesale routes.
 */
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
        Route::controller(WholesaleController::class)->prefix('wholesale')->group(function () {

            Route::get('', 'index')->name('admin.wholesale.index');
            Route::get('create', 'create')->name('admin.wholesale.create');
            Route::get('search', 'search')->name('admin.wholesale.product.search');
            Route::post('create', 'store')->name('admin.wholesale.store');
            Route::put('edit', 'update')->name('admin.wholesale.update');
            Route::delete('edit/{id}', 'destroy')->name('admin.wholesale.delete');
        });
});