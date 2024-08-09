<?php

use Illuminate\Support\Facades\Route;
use Hitexis\PrintCalculator\Http\Controllers\Admin\PrintCalculatorController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin/printcalculator'], function () {
    Route::controller(PrintCalculatorController::class)->group(function () {
        Route::get('', 'index')->name('admin.printcalculator.index');
    });
});