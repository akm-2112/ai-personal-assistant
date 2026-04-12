<?php

namespace Modules\ExpenseTracker\Actions;

use Modules\ExpenseTracker\Models\Expense;

class CreateExpenseAction
{
    public function handle(array $data)
    {
        return \Modules\ExpenseTracker\Models\Expense::create([
            'user_id' => $data['user_id'] ?? 1,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'MMK',
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'date' => $data['date'],
        ]);
        return $expense;
    }
}
