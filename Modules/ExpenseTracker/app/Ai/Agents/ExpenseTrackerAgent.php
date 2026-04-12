<?php

namespace Modules\ExpenseTracker\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;
use Modules\ExpenseTracker\Ai\Tools\RecordExpense;

class ExpenseTrackerAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are an intelligent financial personal assistant. Your job is to analyze the user\'s message and determine if they are logging an expense. If they mention spending money, buying something, or an expense, use your "record_expense" tool to log it mathematically. Keep friendly conversational tones.';
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
     * @return \Laravel\Ai\Contracts\Tool[]|iterable
     */
    public function tools(): iterable
    {
        return [
            new RecordExpense,
        ];
    }
}
