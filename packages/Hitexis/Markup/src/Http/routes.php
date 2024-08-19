<?php
// use Illuminate\Support\Facades\Route;
// use Hitexis\Markup\Http\Controllers\MarkupController;
Route::group(['middleware' => ['web', 'admin']], function () {
    Route::prefix('admin/markup')->group(function () {
        Route::get('/', 'Hitexis\Markup\Http\Controllers\MarkupController@index')->name('markup.markup.index');
        Route::get('/create', 'Hitexis\Markup\Http\Controllers\MarkupController@create')->name('markup.markup.create');
        Route::get('/search', 'Hitexis\Markup\Http\Controllers\MarkupController@search')->name('markup.markup.product.search');
        Route::post('/store', 'Hitexis\Markup\Http\Controllers\MarkupController@store')->name('markup.markup.store');
        Route::get('/edit/{id}', 'Hitexis\Markup\Http\Controllers\MarkupController@edit')->name('markup.markup.edit');
        Route::post('/update/{id}', 'Hitexis\Markup\Http\Controllers\MarkupController@update')->name('markup.markup.update');
        Route::delete('/delete/{id}', 'Hitexis\Markup\Http\Controllers\MarkupController@destroy')->name('markup.markup.delete');
    });
});