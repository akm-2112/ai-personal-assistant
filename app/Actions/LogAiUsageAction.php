<?php

namespace App\Actions;

use App\Models\AiRun;
use App\Models\AiUsage;
use App\Models\User;

class LogAiUsageAction
{
    /**
     * Start a new AI Run session.
     */
    public function start(User $user, string $module, string $input): AiRun
    {
        return AiRun::create([
            'user_id' => $user->id,
            'module' => $module,
            'input' => $input,
            'status' => 'pending',
            'started_at' => now(),
        ]);
    }

    /**
     * Record a specific LLM API call usage during a run.
     */
    public function record(AiRun $run, array $data): AiUsage
    {
        return $run->usages()->create([
            'model' => $data['model'],
            'provider' => $data['provider'] ?? 'openai',
            'prompt_tokens' => $data['prompt_tokens'] ?? 0,
            'completion_tokens' => $data['completion_tokens'] ?? 0,
            'total_tokens' => $data['total_tokens'] ?? 0,
            'cost' => $data['cost'] ?? 0,
            'input' => $data['input'] ?? null,
            'output' => $data['output'] ?? null,
        ]);
    }

    /**
     * Finalize the AI Run session with the final output.
     */
    public function finish(AiRun $run, string $output, array $metadata = []): bool
    {
        return $run->update([
            'output' => $output,
            'status' => 'completed',
            'ended_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Mark the AI Run session as failed.
     */
    public function fail(AiRun $run, string $errorMessage): bool
    {
        return $run->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'ended_at' => now(),
        ]);
    }
}
