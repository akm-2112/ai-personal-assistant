<?php

use Modules\ExpenseTracker\Models\Expense;
use Modules\ExpenseTracker\Ai\Tools\RecordExpense;
use Laravel\Ai\Tools\Request as AiRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('records an expense accurately when the AI tool is called', function () {
    // We need a user to bypass foreign key constraint
    User::factory()->create(['id' => 1]);

    $tool = new RecordExpense();
    
    // Simulate the AI analyzing a sentence and structured JSON tools
    $request = new AiRequest([
        'amount' => 1500,
        'description' => 'Large iced latte',
        'category' => 'Food',
        'date' => '2026-04-12'
    ], null);

    $response = $tool->handle($request);

    expect((string) $response)
        ->toContain('Successfully logged expense of 1500');

    expect(Expense::count())->toBe(1);
    
    $expense = Expense::first();
    expect($expense->amount)->toEqual(1500)
        ->and($expense->description)->toBe('Large iced latte')
        ->and($expense->category)->toBe('Food');
});
