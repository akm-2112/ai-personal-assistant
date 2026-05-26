<?php

namespace Modules\CvWriter\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\CvWriter\Services\KnowledgeIngestionService;
use Stringable;

class SearchKnowledgeTool implements Tool
{
    public function __construct(
        private readonly KnowledgeIngestionService $ingestion
    ) {}

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Search the personal knowledge base using semantic search. Use this to find relevant skills, experience, projects, and background about the candidate that match the job description requirements. Call this multiple times with different queries if needed.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $query = $request['query'] ?? '';

        if (empty($query)) {
            return 'Please provide a valid query string.';
        }

        $results = $this->ingestion->searchSimilar($query, 8);

        if (empty($results)) {
            return "No relevant knowledge found for query: {$query}";
        }

        return collect($results)
            ->map(fn ($r) => "[{$r->file_title}]\n{$r->content}")
            ->implode("\n\n---\n\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('The semantic search query, e.g., "Laravel projects" or "Work history".')
                ->required(),
        ];
    }
}
