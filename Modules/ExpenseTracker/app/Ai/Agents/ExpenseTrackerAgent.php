<?php

namespace Modules\ExpenseTracker\Ai\Agents;

use App\Actions\LogAiUsageAction;
use App\Models\User;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseList;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseSummary;
use Modules\ExpenseTracker\Ai\Tools\RecordExpense;
use Modules\ExpenseTracker\Classes\Ai\Support\ExpenseChatOrchestrationRules;
use Stringable;

#[MaxTokens(1000)]
class ExpenseTrackerAgent implements Agent, Conversational, HasTools
{
    use Promptable;
    use RemembersConversations;

    public function __construct(public ?User $user = null) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return implode("\n\n", [
            'You are an intelligent financial personal assistant focused on expense tracking only.',
            ExpenseChatOrchestrationRules::asPrompt(),
        ]);
    }

    /**
     * Ask the agent a question and log the usage.
     */
    public function askAndLog(User $user, string $prompt, ?string $conversationId = null)
    {
        $this->user = $user;

        if ($conversationId !== null && $conversationId !== '') {
            $this->continue($conversationId, as: $user);
        } else {
            $this->forUser($user);
        }

        $logger = app(LogAiUsageAction::class);
        $run = $logger->start($user, 'ExpenseTracker', $prompt);

        try {
            $provider = $this->resolveProvider();
            $model = config("ai.providers.{$provider}.models.text.default");

            $response = $this->prompt(
                $prompt,
                provider: $provider,
                model: is_string($model) ? $model : null,
            );

            $logger->record($run, [
                'model' => is_string($model) ? $model : '',
                'provider' => $provider,
                'prompt_tokens' => $response->usage->promptTokens,
                'completion_tokens' => $response->usage->completionTokens,
                'total_tokens' => $response->usage->promptTokens + $response->usage->completionTokens,
                'input' => $prompt,
                'output' => $response->text,
            ]);

            $logger->finish($run, $response->text, [
                'conversation_id' => $response->conversationId,
            ]);

            return $response;
        } catch (\Exception $e) {
            $logger->fail($run, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Resolve runtime provider for tool-compatible chat.
     */
    private function resolveProvider(): string
    {
        return (string) config('ai.default', 'openai');
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]|iterable
     */
    public function tools(): iterable
    {
        $userId = $this->user?->id;

        return [
            new RecordExpense($userId),
            new GetExpenseSummary($userId),
            new GetExpenseList($userId),
        ];
    }
}
