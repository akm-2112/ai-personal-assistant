<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpenseTracker\Http\Controllers\Api\ExpenseChatController;
use Modules\ExpenseTracker\Http\Controllers\Api\ExpenseController;

Route::middleware('api')->prefix('expenses')->group(function () {
    Route::get('/', [ExpenseController::class, 'index']);
    Route::post('/', [ExpenseController::class, 'store']);
});

Route::middleware(['api', 'auth'])->prefix('expense-tracker/chat')->group(function () {
    Route::post('/send', [ExpenseChatController::class, 'send'])->name('api.expense-tracker.chat.send');
});
