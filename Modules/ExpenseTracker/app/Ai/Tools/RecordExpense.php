<?php

namespace Modules\ExpenseTracker\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\ExpenseTracker\Models\Expense;
use Stringable;

class RecordExpense implements Tool
{
    public function __construct(private readonly ?int $defaultUserId = null) {}

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
        $currency = $request['currency'] ?? 'MMK';
        $description = $request['description'] ?? 'Misc Expense';
        $category = $request['category'] ?? 'Other';
        $date = $request['date'] ?? now()->format('Y-m-d');
        $userId = $request['user_id'] ?? $this->defaultUserId ?? auth()->id() ?? 1;

        if ($amount <= 0) {
            return 'Failed to record expense. You must provide a valid numerical amount greater than 0.';
        }

        Expense::create([
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => $currency,
            'description' => $description,
            'category' => $category,
            'date' => $date,
        ]);

        return "Successfully logged expense of $amount $currency for '$description'.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'amount' => $schema->number()->min(0.01)->description('Numeric amount spent, for example 5.00.')->required(),
            'currency' => $schema->string()->description('Optional 3-letter currency code, for example MMK or USD. Defaults to MMK.'),
            'description' => $schema->string()->description('Optional description of the expense, for example coffee or movie ticket.'),
            'category' => $schema->string()->description('Expense category, for example Food, Transport, Utilities, Entertainment, Shopping, Other.')->required(),
            'date' => $schema->string()->description('Optional expense date in YYYY-MM-DD format. Defaults to today.'),
            'user_id' => $schema->integer()->description('Optional user id. Defaults to authenticated user context.'),
        ];
    }
}
