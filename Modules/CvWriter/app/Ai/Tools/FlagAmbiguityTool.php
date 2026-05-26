<?php

namespace Modules\CvWriter\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FlagAmbiguityTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Use this ONLY when the job description is genuinely unclear and you cannot proceed without more information. Pass an array of specific questions to ask the user.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $questions = $request['questions'] ?? [];

        if (empty($questions)) {
            return 'Failed to flag ambiguity. You must provide a list of clarifying questions.';
        }

        return json_encode([
            'status' => 'ambiguity_flagged',
            'questions' => $questions,
        ]);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'questions' => $schema->array()
                ->items($schema->string())
                ->description('An array of specific questions to ask the user to clarify the job description.')
                ->required(),
        ];
    }
}
