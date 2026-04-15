<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpenseTracker\Http\Controllers\Web\ExpenseChatController;
use Modules\ExpenseTracker\Http\Controllers\Web\ExpenseController;

Route::middleware(['auth', 'verified'])->prefix('expense-tracker')->group(function () {
    Route::get('/', [ExpenseController::class, 'index'])->name('expense-tracker.index');
    Route::post('/', [ExpenseController::class, 'store'])->name('expense-tracker.store');
});

Route::middleware(['auth', 'verified'])
    ->post('chat/expense/send', [ExpenseChatController::class, 'send'])
    ->name('expense-tracker.chat.send');
