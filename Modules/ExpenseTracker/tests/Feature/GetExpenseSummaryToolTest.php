<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request as AiRequest;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseSummary;
use Modules\ExpenseTracker\Models\Expense;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns expense summary for the current month', function () {
    User::factory()->create(['id' => 1]);

    Expense::factory()->create([
        'user_id' => 1,
        'amount' => 1000,
        'category' => 'Food',
        'date' => now()->startOfMonth()->addDay()->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => 1,
        'amount' => 500,
        'category' => 'Transport',
        'date' => now()->startOfMonth()->addDays(2)->toDateString(),
    ]);

    Expense::factory()->create([
        'user_id' => 1,
        'amount' => 700,
        'category' => 'Food',
        'date' => now()->startOfMonth()->addDays(3)->toDateString(),
    ]);

    $tool = new GetExpenseSummary;
    $response = $tool->handle(new AiRequest([
        'period' => 'month',
        'currency' => 'MMK',
        'user_id' => 1,
    ], null));

    $summary = json_decode((string) $response, true);

    expect($summary)->toBeArray()
        ->and($summary['total'])->toEqual(2200.0)
        ->and($summary['count'])->toBe(3)
        ->and($summary['average'])->toEqual(733.33)
        ->and($summary['top_category'])->toBe('Food')
        ->and($summary['currency'])->toBe('MMK');
});

it('returns a helpful message when no expenses are found', function () {
    User::factory()->create(['id' => 1]);

    $tool = new GetExpenseSummary;
    $response = $tool->handle(new AiRequest([
        'period' => 'today',
        'user_id' => 1,
    ], null));

    expect((string) $response)->toContain('No expenses found');
});
