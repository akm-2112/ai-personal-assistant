<?php

namespace Modules\ExpenseTracker\Actions;

use App\Actions\LogAiUsageAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ExpenseTracker\Ai\Agents\ExpenseTrackerAgent;

class HandleExpenseChatAction
{
    public function __construct() {}

    /**
     * Send a user message to the Expense Tracker agent and return a normalized payload.
     *
     * @return array{
     *     reply: string,
     *     conversation_id: string|null,
     *     tool_calls: array<int, array<string, mixed>>,
     *     tool_results: array<int, array<string, mixed>>,
     *     usage: array<string, int>,
     *     module: string,
     *     ui_hints: array{open_module: string}
     * }
     */
    public function handle(User $user, string $message, ?string $conversationId = null): array
    {
        $this->ensureConversationBelongsToUser($user, $conversationId);

        $agent = ExpenseTrackerAgent::make(user: $user);

        // We delegate parsing, prompt generation, and usage logging to the customized Agent
        $response = $agent->askAndLog($user, $message, $conversationId);

        return [
            'reply' => $response->text,
            'conversation_id' => $response->conversationId,
            'tool_calls' => $response->toolCalls->map(fn ($toolCall) => $toolCall->toArray())->values()->all(),
            'tool_results' => $response->toolResults->map(fn ($toolResult) => $toolResult->toArray())->values()->all(),
            'usage' => $response->usage->toArray(),
            'module' => 'expense_tracker',
            'ui_hints' => [
                'open_module' => route('expense-tracker.index'),
            ],
        ];
    }

    private function ensureConversationBelongsToUser(User $user, ?string $conversationId): void
    {
        if ($conversationId === null || $conversationId === '') {
            return;
        }

        $exists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'conversation_id' => 'The selected conversation is invalid for the current user.',
            ]);
        }
    }
}
