<?php

use Illuminate\Support\Facades\Route;
use Hitexis\PrintCalculator\Http\Controllers\Shop\PrintCalculatorController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency'], 'prefix' => 'printcalculator'], function () {
    Route::get('', [PrintCalculatorController::class, 'index'])->name('shop.printcalculator.index');
});