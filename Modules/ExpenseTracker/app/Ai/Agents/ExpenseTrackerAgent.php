<?php

namespace Modules\ExpenseTracker\Ai\Agents;

use App\Actions\LogAiUsageAction;
use App\Models\User;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Modules\ExpenseTracker\Ai\Tools\GetExpenseSummary;
use Modules\ExpenseTracker\Ai\Tools\RecordExpense;
use Stringable;

#[Provider(Lab::OpenAI)]
#[UseCheapestModel]
#[MaxTokens(1000)]
class ExpenseTrackerAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
        You are an intelligent financial personal assistant. Your job is to analyze the user's message and determine if they are logging an expense. If they mention spending money, buying something, or an expense, use your "record_expense" tool to log it mathematically. Stay strictly within the expense tracker domain. If the user asks for something else, politely decline. If you are unsure, ask a clarifying question.
        PROMPT;
    }

    /**
     * Ask the agent a question and log the usage.
     */
    public function askAndLog(User $user, string $prompt)
    {
        $logger = app(LogAiUsageAction::class);
        $run = $logger->start($user, 'ExpenseTracker', $prompt);

        try {
            $response = $this->ask($prompt);

            $logger->record($run, [
                'model' => 'gpt-4o-mini', // Update based on your configuration
                'provider' => 'openai',
                'prompt_tokens' => $response->usage()->promptTokens,
                'completion_tokens' => $response->usage()->completionTokens,
                'total_tokens' => $response->usage()->totalTokens,
                'input' => $prompt,
                'output' => $response->content(),
            ]);

            $logger->finish($run, $response->content());

            return $response;
        } catch (\Exception $e) {
            $logger->fail($run, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]|iterable
     */
    public function tools(): iterable
    {
        return [
            new RecordExpense,
            new GetExpenseSummary,
        ];
    }
}
