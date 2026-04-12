<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpenseTracker\Http\Controllers\ExpenseController;

Route::middleware('api')->prefix('expenses')->group(function () {
    Route::get('/', [ExpenseController::class, 'index']);
    Route::post('/', [ExpenseController::class, 'store']);
});
