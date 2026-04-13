<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpenseTracker\Http\Controllers\Web\ExpenseController;

Route::middleware(['auth', 'verified'])->prefix('expense-tracker')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('expense-tracker.index');
    Route::post('/', [ExpenseController::class, 'store'])->name('expense-tracker.store');
});
