<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\Tools\Request as AiRequest;
use Modules\CvWriter\Ai\Tools\FlagAmbiguityTool;
use Modules\CvWriter\Ai\Tools\GetProfileSummaryTool;
use Modules\CvWriter\Ai\Tools\SearchKnowledgeTool;
use Modules\CvWriter\Enums\CategoryType;
use Modules\CvWriter\Models\KnowledgeFile;
use Modules\CvWriter\Services\KnowledgeIngestionService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('SearchKnowledgeTool returns matched chunks', function () {
    Ai::fakeEmbeddings();

    $file = KnowledgeFile::create([
        'title' => 'Laravel Experience',
        'category' => CategoryType::EXPERIENCE,
        'raw_content' => 'I have built multiple enterprise applications using Laravel.',
        'is_active' => true,
    ]);

    $ingestion = app(KnowledgeIngestionService::class);
    $ingestion->ingest($file);

    $tool = app(SearchKnowledgeTool::class);
    $request = new AiRequest([
        'query' => 'Laravel development',
    ], null);

    $response = $tool->handle($request);

    expect((string) $response)->toContain('Laravel');
    expect((string) $response)->toContain('[Laravel Experience]');
});

test('GetProfileSummaryTool returns active profile documents only', function () {
    KnowledgeFile::create([
        'title' => 'Active Skillset',
        'category' => CategoryType::SKILLS,
        'raw_content' => 'PHP, JavaScript, SQL',
        'is_active' => true,
    ]);

    KnowledgeFile::create([
        'title' => 'Deprecated Experience',
        'category' => CategoryType::EXPERIENCE,
        'raw_content' => 'Flash and ActionScript',
        'is_active' => false,
    ]);

    $tool = app(GetProfileSummaryTool::class);
    $request = new AiRequest([], null);

    $response = $tool->handle($request);

    expect((string) $response)->toContain('PHP, JavaScript, SQL');
    expect((string) $response)->not->toContain('Flash and ActionScript');
});

test('FlagAmbiguityTool formats questions to json', function () {
    $tool = app(FlagAmbiguityTool::class);
    $request = new AiRequest([
        'questions' => [
            'What is the preferred tech stack?',
            'Is this role remote or hybrid?',
        ],
    ], null);

    $response = $tool->handle($request);

    $decoded = json_decode((string) $response, true);
    expect($decoded['status'])->toBe('ambiguity_flagged');
    expect($decoded['questions'])->toContain('What is the preferred tech stack?');
    expect($decoded['questions'])->toContain('Is this role remote or hybrid?');
});
