<?php
// use Illuminate\Support\Facades\Route;
use Hitexis\Wholesale\Http\Controllers\WholesaleController;
Route::group(['middleware' => ['web', 'admin']], function () {
    Route::prefix('admin/wholesale')->group(function () {
        Route::get('/', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@index')->name('wholesale.wholesale.index');
        Route::get('/create', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@create')->name('wholesale.wholesale.create');
        Route::get('/search', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@search')->name('wholesale.wholesale.product.search');
        Route::post('/store', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@store')->name('wholesale.wholesale.store');
        Route::get('/edit/{id}', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@edit')->name('wholesale.wholesale.edit');
        Route::post('/update/{id}', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@update')->name('wholesale.wholesale.update');
        Route::delete('/delete/{id}', 'Hitexis\Wholesale\Http\Controllers\WholesaleController@destroy')->name('wholesale.wholesale.delete');
    });
});