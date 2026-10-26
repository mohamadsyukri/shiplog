<?php

use App\Http\Controllers\Imports\ImportController;
use Illuminate\Support\Facades\Route;

Route::prefix('imports')->name('imports.')->controller(ImportController::class)->group(function (): void {
    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
});
