<?php

use Modules\ExpenseTracker\Ai\Agents\ExpenseTrackerAgent;

it('contains explicit orchestration tie-breakers for summary vs list tools', function () {
    $instructions = (string) (new ExpenseTrackerAgent)->instructions();

    expect($instructions)
        ->toContain('Use GetExpenseSummary when the user asks aggregate insights.')
        ->toContain('Use GetExpenseList when the user asks for itemized entries')
        ->toContain('If user says "how much" or asks a numeric total, prefer GetExpenseSummary.')
        ->toContain('If user says "what did I spend" / "show" / "list" / "transactions" / "entries", prefer GetExpenseList.');
});
