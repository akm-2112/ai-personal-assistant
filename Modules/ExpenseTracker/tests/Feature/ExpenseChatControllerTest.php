<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\ExpenseTracker\Ai\Agents\ExpenseTrackerAgent;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can send an expense chat message via web endpoint and persist conversation', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    ExpenseTrackerAgent::fake(['Your spending total this month is 25,000 MMK.']);

    $response = $this->actingAs($user)->postJson('/chat/expense/send', [
        'message' => 'How much did I spend this month?',
    ]);

    $response->assertOk()
        ->assertJsonPath('reply', 'Your spending total this month is 25,000 MMK.')
        ->assertJsonStructure([
            'reply',
            'conversation_id',
            'tool_calls',
            'tool_results',
            'usage',
            'module',
            'ui_hints' => ['open_module'],
        ]);

    $conversationId = $response->json('conversation_id');

    expect($conversationId)->toBeString()->not->toBe('');

    $this->assertDatabaseHas('agent_conversations', [
        'id' => $conversationId,
        'user_id' => $user->id,
    ]);

    expect(DB::table('agent_conversation_messages')
        ->where('conversation_id', $conversationId)
        ->count())->toBe(2);
});

it('can continue an existing expense chat conversation', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    ExpenseTrackerAgent::fake(function (string $prompt): string {
        return str_contains(strtolower($prompt), 'yesterday')
            ? 'Second answer'
            : 'First answer';
    });

    $firstResponse = $this->actingAs($user)->postJson('/chat/expense/send', [
        'message' => 'How much did I spend last month?',
    ])->assertOk();

    $conversationId = $firstResponse->json('conversation_id');

    $secondResponse = $this->actingAs($user)->postJson('/chat/expense/send', [
        'message' => 'What did I spend yesterday?',
        'conversation_id' => $conversationId,
    ]);

    $secondResponse->assertOk()
        ->assertJsonPath('conversation_id', $conversationId)
        ->assertJsonPath('reply', 'Second answer');

    expect(DB::table('agent_conversation_messages')
        ->where('conversation_id', $conversationId)
        ->count())->toBe(4);
});

it('can send an expense chat message via api endpoint', function () {
    $user = User::factory()->create();

    ExpenseTrackerAgent::fake(['API endpoint response']);

    $response = $this->actingAs($user)->postJson('/api/expense-tracker/chat/send', [
        'message' => 'Show my latest expense',
    ]);

    $response->assertOk()
        ->assertJsonPath('reply', 'API endpoint response')
        ->assertJsonStructure([
            'reply',
            'conversation_id',
            'tool_calls',
            'tool_results',
            'usage',
            'module',
        ]);
});
