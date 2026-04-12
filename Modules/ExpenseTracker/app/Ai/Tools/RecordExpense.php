<?php

namespace Modules\ExpenseTracker\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\ExpenseTracker\Models\Expense;
use Stringable;

class RecordExpense implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Record a new financial expense. Use this whenever the user mentions spending money.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $amount = $request['amount'] ?? 0;
        $description = $request['description'] ?? 'Misc Expense';
        $category = $request['category'] ?? 'Other';
        $date = $request['date'] ?? now()->format('Y-m-d');

        if ($amount <= 0) {
            return 'Failed to record expense. You must provide a valid numerical amount greater than 0.';
        }

        Expense::create([
            'user_id' => 1, // Defaulting to 1 for demo purposes
            'amount' => $amount,
            'currency' => 'MMK',
            'description' => $description,
            'category' => $category,
            'date' => $date,
        ]);

        return "Successfully logged expense of $amount MMK for '$description'.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => 'Numeric amount spent (e.g., 5.00)',
            'description' => 'What exactly was bought (e.g., Coffee, Movie ticket)',
            'category' => 'Category (Food, Transport, Utilities, Entertainment, Shopping, Other)',
            'date' => 'Date of the expense in YYYY-MM-DD format. Default to today if not specified.',
        ];
    }
}
