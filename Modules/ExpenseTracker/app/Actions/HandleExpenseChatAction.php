<?php

namespace Modules\ExpenseTracker\Actions;

use App\Actions\LogAiUsageAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\ExpenseTracker\Ai\Agents\ExpenseTrackerAgent;

class HandleExpenseChatAction
{
    public function __construct(private readonly LogAiUsageAction $usageLogger) {}

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

        if ($conversationId !== null && $conversationId !== '') {
            $agent->continue($conversationId, as: $user);
        } else {
            $agent->forUser($user);
        }

        $run = $this->usageLogger->start($user, 'ExpenseTracker', $message);

        try {
            $provider = $this->resolveProvider();
            $model = config("ai.providers.{$provider}.models.text.default");

            $response = $agent->prompt(
                $message,
                provider: $provider,
                model: is_string($model) ? $model : null,
            );

            $this->usageLogger->record($run, [
                'model' => is_string($model) ? $model : '',
                'provider' => $provider,
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'total_tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
                'input' => ['message' => $message],
                'output' => ['text' => $response->text],
            ]);

            $this->usageLogger->finish($run, $response->text, [
                'conversation_id' => $response->conversationId,
                'tool_calls_count' => $response->toolCalls->count(),
                'tool_results_count' => $response->toolResults->count(),
            ]);

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
        } catch (\Throwable $exception) {
            $this->usageLogger->fail($run, $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Resolve runtime provider for tool-compatible chat.
     */
    private function resolveProvider(): string
    {
        return (string) config('ai.default', 'openai');
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
