<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Modules\CvWriter\Enums\CategoryType;
use Modules\CvWriter\Models\KnowledgeChunk;
use Modules\CvWriter\Models\KnowledgeFile;
use Modules\CvWriter\Services\KnowledgeIngestionService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('can split raw content into chunks, generate embeddings and store them', function () {
    // Fake the AI embeddings generation
    Ai::fakeEmbeddings();

    // Create a mock KnowledgeFile
    $file = KnowledgeFile::create([
        'title' => 'My Skills and Projects',
        'category' => CategoryType::SKILLS,
        'raw_content' => str_repeat('This is a paragraph about skills. It should be long enough to chunk. ', 50),
        'is_active' => true,
    ]);

    $service = app(KnowledgeIngestionService::class);
    $service->ingest($file);

    // Verify chunking and embedding storage
    $chunks = KnowledgeChunk::where('knowledge_file_id', $file->id)->get();
    expect($chunks)->not->toBeEmpty();
    expect($chunks->first()->content)->not->toBeEmpty();
    expect($chunks->first()->embedding)->not->toBeEmpty();
    expect($chunks->first()->metadata['file_title'])->toBe('My Skills and Projects');
    expect($chunks->first()->metadata['category'])->toBe('skills');

    // Verify last_ingested_at is updated
    $file->refresh();
    expect($file->last_ingested_at)->not->toBeNull();
});

it('can search for similar chunks using semantic similarity search', function () {
    // Fake the AI embeddings generation
    Ai::fakeEmbeddings();

    // Create active and inactive knowledge files
    $activeFile = KnowledgeFile::create([
        'title' => 'Active Experience',
        'category' => CategoryType::EXPERIENCE,
        'raw_content' => 'I worked as a Full Stack Laravel Developer for three years.',
        'is_active' => true,
    ]);

    $inactiveFile = KnowledgeFile::create([
        'title' => 'Old Experience',
        'category' => CategoryType::EXPERIENCE,
        'raw_content' => 'I worked as a Java Developer 10 years ago.',
        'is_active' => false,
    ]);

    $service = app(KnowledgeIngestionService::class);
    $service->ingest($activeFile);
    $service->ingest($inactiveFile);

    // Search similar
    $results = $service->searchSimilar('Laravel developer', limit: 5);

    expect($results)->not->toBeEmpty();
    // It should only return chunks from active files
    foreach ($results as $result) {
        expect($result->file_title)->toBe('Active Experience');
        expect($result->content)->toContain('Laravel');
        expect($result->similarity)->toBeGreaterThanOrEqual(0.0);
    }
});
