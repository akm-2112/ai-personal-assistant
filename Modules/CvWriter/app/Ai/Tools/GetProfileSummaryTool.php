<?php

namespace Modules\CvWriter\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\CvWriter\Models\KnowledgeFile;
use Stringable;

class GetProfileSummaryTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Get a full structured summary of all active knowledge files about the candidate. Use this at the start to understand the candidate\'s full profile before doing targeted searches.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $files = KnowledgeFile::where('is_active', true)
            ->select('title', 'category', 'raw_content')
            ->get();

        if ($files->isEmpty()) {
            return 'No knowledge files found. The knowledge base is empty.';
        }

        return $files->map(function ($file) {
            $categoryName = $file->category->value ?? $file->category;

            return "=== {$file->title} (category: {$categoryName}) ===\n{$file->raw_content}";
        })->implode("\n\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
