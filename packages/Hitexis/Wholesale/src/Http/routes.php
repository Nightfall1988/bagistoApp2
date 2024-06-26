<?php
// use Illuminate\Support\Facades\Route;
use Hitexis\Wholesale\Http\Controllers\WholesaleController;
Route::group(['middleware' => ['web', 'admin']], function () {
    Route::prefix('admin/wholesale')->group(function () {
        Route::get('/', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@index')->name('wholesale.wholesale.index');
        Route::get('/create', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@create')->name('wholesale.wholesale.create');
        Route::get('/search', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@search')->name('wholesale.wholesale.product.search');
        Route::post('/create', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@store')->name('wholesale.wholesale.store');
        Route::put('/edit', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@update')->name('wholesale.wholesale.update');
        Route::delete('/edit/{id}', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@destroy')->name('wholesale.wholesale.delete');
    });
});