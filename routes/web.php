<?php

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProductUnitsController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SalesChartController;
use App\Http\Controllers\FocusListController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::post('/focus-list/toggle', [FocusListController::class, 'toggle'])->name('focus.toggle');
Route::get('/dashboard', [SalesChartController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');
Route::get('/report', [SalesChartController::class, 'salesReport'])
    ->middleware(['auth'])
    ->name('report.sales');

/* The backtick character (`) in the code snippet you provided is not performing any specific functionality within the PHP code. In this context, it appears to be used as a delimiter or separator to indicate the end of the PHP code block and the beginning of a comment section. */
Route::resource('products', ProductController::class)->middleware(['auth'])->names('products');
Route::resource('product_units', ProductUnitsController::class)->middleware(['auth'])->names('product_units');
Route::resource('inventories', InventoryController::class)
    ->middleware(['auth']);
Route::resource('processes', controller: ProcessController::class)->middleware(['auth'])->names('processes');
 Route::post(
        'processes/{inventory}/approve',
        [ProcessController::class, 'approve']
    )->name('processes.approve');
    Route::post('/processes/store-bulk', [ProcessController::class, 'storeBulk'])->middleware(['auth'])
    ->name('processes.store.bulk');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
});
require __DIR__.'/auth.php';
