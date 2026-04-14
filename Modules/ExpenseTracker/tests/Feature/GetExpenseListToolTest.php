<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request as AiRequest;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseList;
use Modules\ExpenseTracker\Models\Expense;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns a capped list of expenses for yesterday', function () {
    User::factory()->create(['id' => 1]);

    Expense::factory()->count(7)->create([
        'user_id' => 1,
        'category' => 'Food',
        'date' => now()->subDay()->toDateString(),
    ]);

    $tool = new GetExpenseList;
    $response = $tool->handle(new AiRequest([
        'period' => 'yesterday',
        'user_id' => 1,
        'limit' => 5,
    ], null));

    $payload = json_decode((string) $response, true);

    expect($payload)->toBeArray()
        ->and($payload['count'])->toBe(7)
        ->and($payload['limit'])->toBe(5)
        ->and($payload['truncated'])->toBeTrue()
        ->and($payload['items'])->toHaveCount(5);
});

it('filters expense list by category', function () {
    User::factory()->create(['id' => 1]);

    Expense::factory()->count(2)->create([
        'user_id' => 1,
        'category' => 'Food',
        'date' => now()->toDateString(),
    ]);

    Expense::factory()->count(2)->create([
        'user_id' => 1,
        'category' => 'Transport',
        'date' => now()->toDateString(),
    ]);

    $tool = new GetExpenseList;
    $response = $tool->handle(new AiRequest([
        'period' => 'today',
        'user_id' => 1,
        'category' => 'Food',
    ], null));

    $payload = json_decode((string) $response, true);

    expect($payload)->toBeArray()
        ->and($payload['count'])->toBe(2)
        ->and(collect($payload['items'])->pluck('category')->unique()->all())->toEqual(['Food']);
});
