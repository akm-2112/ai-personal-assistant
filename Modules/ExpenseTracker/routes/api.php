<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpenseTracker\Http\Controllers\Api\ExpenseController;

Route::middleware('api')->prefix('expenses')->group(function () {
    Route::get('/', [ExpenseController::class, 'index']);
    Route::post('/', [ExpenseController::class, 'store']);
});
