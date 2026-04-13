<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ExpenseTracker\Models\Expense;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can fetch expenses for a user via api route', function () {
    /** @var User $user */
    $user = User::factory()->create();

    // Create 3 expenses for this user
    Expense::factory()->count(3)->create([
        'user_id' => $user->id,
    ]);

    // Make an API request to the newly built route
    $response = $this->actingAs($user)->getJson('/api/expenses');

    $response->assertStatus(200)
        ->assertJsonCount(3);
});

it('can store a new manual expense', function () {
    /** @var User $user */
    $user = User::factory()->create();
    $payload = [
        'amount' => 500,
        'currency' => 'MMK',
        'description' => 'A manual expense test',
        'category' => 'Food',
        'date' => '2026-04-12',
    ];

    $response = $this->actingAs($user)->postJson('/api/expenses', $payload);

    $response->assertStatus(201);

    expect(Expense::count())->toBe(1);
    expect(Expense::first()->amount)->toEqual(500);
});
