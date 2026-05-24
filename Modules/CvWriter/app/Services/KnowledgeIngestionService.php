<?php

namespace Modules\CvWriter\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Ai;
use Modules\CvWriter\Models\KnowledgeChunk;
use Modules\CvWriter\Models\KnowledgeFile;

class KnowledgeIngestionService
{
    // ~400 tokens per chunk (1 token ≈ 4 chars)
    public const CHUNK_SIZE = 1600;

    public const CHUNK_OVERLAP = 200;

    /**
     * Ingest a KnowledgeFile by splitting it into chunks, generating embeddings, and storing them.
     */
    public function ingest(KnowledgeFile $file): void
    {
        Log::info("Ingesting knowledge file: {$file->title}");

        // Delete old chunks for this file
        KnowledgeChunk::where('knowledge_file_id', $file->id)->delete();

        $chunks = $this->splitIntoChunks($file->raw_content);

        foreach ($chunks as $index => $chunkText) {
            // Get embedding from AI provider (Gemini via Laravel AI SDK)
            $embedding = $this->embed($chunkText);

            // Insert using raw DB to handle the vector type
            DB::table('knowledge_chunks')->insert([
                'knowledge_file_id' => $file->id,
                'chunk_index' => $index,
                'content' => $chunkText,
                'embedding' => $embedding,
                'metadata' => json_encode([
                    'file_title' => $file->title,
                    'category' => $file->category->value ?? $file->category,
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $file->update(['last_ingested_at' => now()]);

        Log::info("Ingested {$file->title}: ".count($chunks).' chunks created.');
    }

    /**
     * Search the personal knowledge base using semantic search (pgvector similarity search).
     */
    public function searchSimilar(string $query, int $limit = 8): array
    {
        $queryEmbedding = $this->embed($query);

        // pgvector cosine similarity search
        return DB::select('
            SELECT
                kc.content,
                kc.metadata,
                kf.title as file_title,
                1 - (kc.embedding <=> :embedding::vector) as similarity
            FROM knowledge_chunks kc
            JOIN knowledge_files kf ON kf.id = kc.knowledge_file_id
            WHERE kf.is_active = true
            ORDER BY kc.embedding <=> :embedding2::vector
            LIMIT :limit
        ', [
            'embedding' => $queryEmbedding,
            'embedding2' => $queryEmbedding,
            'limit' => $limit,
        ]);
    }

    /**
     * Call the Laravel AI SDK to generate embeddings and format as a pgvector string.
     */
    private function embed(string $text): string
    {
        $response = Ai::embeddings([$text], 768);
        $vector = $response->embeddings[0];

        // Format: [0.123, 0.456, ...]
        return '['.implode(',', $vector).']';
    }

    /**
     * Split text into chunks of CHUNK_SIZE with CHUNK_OVERLAP.
     */
    private function splitIntoChunks(string $text): array
    {
        $chunks = [];
        $length = strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + self::CHUNK_SIZE, $length);

            // Try to break at paragraph boundary to keep context clean
            if ($end < $length) {
                $slice = substr($text, $start, $end - $start);
                $breakPoint = strrpos($slice, "\n\n");
                if ($breakPoint !== false && $breakPoint > self::CHUNK_SIZE / 2) {
                    $end = $start + $breakPoint;
                }
            }

            $chunk = trim(substr($text, $start, $end - $start));
            if (! empty($chunk)) {
                $chunks[] = $chunk;
            }

            if ($end >= $length) {
                break;
            }

            $start = $end - self::CHUNK_OVERLAP;

            // Safety: avoid infinite loop if start does not progress
            if ($start <= 0) {
                $start = $end;
            }
        }

        return $chunks;
    }
}
