<?php
use Illuminate\Support\Facades\Route;
use Hitexis\Wholesale\Http\Controllers\WholesaleController;
Route::group(['middleware' => ['web', 'admin']], function () {
    Route::prefix('admin/wholesale')->group(function () {
        Route::get('/', 'Hitexis\Admin\Http\Controllers\WholesaleController@index')->name('wholesale.wholesale.index');
        Route::get('/create', 'Hitexis\Admin\Http\Controllers\WholesaleController@create')->name('wholesale.wholesale.create');
        Route::get('/search', 'Hitexis\Admin\Http\Controllers\WholesaleController@search')->name('wholesale.wholesale.product.search');
        Route::post('/create', 'Hitexis\Admin\Http\Controllers\WholesaleController@store')->name('wholesale.wholesale.store');
        Route::put('/edit', 'Hitexis\Admin\Http\Controllers\WholesaleController@update')->name('wholesale.wholesale.update');
        Route::delete('/delete/{id}', 'Hitexis\Admin\Http\Controllers\WholesaleController@destroy')->name('wholesale.wholesale.delete');
    });
});

Route::group(['middleware' => ['web', 'admin']], function () {
    Route::prefix('admin/markup')->group(function () {
        Route::get('/', 'Hitexis\Admin\Http\Controllers\MarkupController@index')->name('markup.markup.index');
        Route::get('/create', 'Hitexis\Admin\Http\Controllers\MarkupController@create')->name('markup.markup.create');
        Route::get('/search', 'Hitexis\Admin\Http\Controllers\MarkupController@search')->name('markup.markup.product.search');
        Route::post('/create', 'Hitexis\Admin\Http\Controllers\MarkupController@store')->name('markup.markup.store');
        Route::put('/edit', 'Hitexis\Admin\Http\Controllers\MarkupController@update')->name('markup.markup.update');
        Route::delete('/delete/{id}', 'Hitexis\Admin\Http\Controllers\MarkupController@destroy')->name('markup.markup.delete');
    });
});