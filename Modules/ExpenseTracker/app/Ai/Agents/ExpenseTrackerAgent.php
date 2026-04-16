<?php

namespace Modules\ExpenseTracker\Ai\Agents;

use App\Actions\LogAiUsageAction;
use App\Models\User;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseList;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseSummary;
use Modules\ExpenseTracker\Ai\Tools\RecordExpense;
use Modules\ExpenseTracker\Ai\Prompts\ExpenseChatOrchestrationRules;
use Stringable;

#[MaxTokens(1000)]
#[Provider('gemini')]
#[UseCheapestModel]
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
            // Because of #[Provider] and #[UseCheapestModel], we don't need to specify provider/model here!
            $response = $this->prompt($prompt);

            $logger->record($run, [
                'model' => $response->responseMeta->model ?? '',
                'provider' => 'gemini',
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
