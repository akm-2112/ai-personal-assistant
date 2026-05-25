<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Modules\CvWriter\Enums\CategoryType;
use Modules\CvWriter\Models\KnowledgeFile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('guests are redirected to login for knowledge files routes', function () {
    $this->get(route('cvwriter.knowledge.index'))->assertRedirect(route('login'));
    $this->get(route('cvwriter.knowledge.create'))->assertRedirect(route('login'));
    $this->post(route('cvwriter.knowledge.store'), [])->assertRedirect(route('login'));
});

test('authenticated users can view the knowledge files list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    KnowledgeFile::create([
        'title' => 'My Work Experience',
        'category' => CategoryType::EXPERIENCE,
        'raw_content' => 'My resume details',
        'is_active' => true,
    ]);

    $response = $this->get(route('cvwriter.knowledge.index'));
    $response->assertOk();
    $response->assertViewHas('files');
});

test('authenticated users can store a new knowledge file which triggers ingestion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Ai::fakeEmbeddings();

    $response = $this->post(route('cvwriter.knowledge.store'), [
        'title' => 'My Skills',
        'category' => 'skills',
        'raw_content' => 'Laravel, React, Tailwind CSS',
    ]);

    $response->assertRedirect(route('cvwriter.knowledge.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('knowledge_files', [
        'title' => 'My Skills',
        'category' => 'skills',
        'raw_content' => 'Laravel, React, Tailwind CSS',
    ]);

    // Verify chunks were created
    $file = KnowledgeFile::where('title', 'My Skills')->first();
    expect($file->chunks)->not->toBeEmpty();
    expect($file->last_ingested_at)->not->toBeNull();
});

test('authenticated users can update a knowledge file which triggers re-ingestion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Ai::fakeEmbeddings();

    $file = KnowledgeFile::create([
        'title' => 'My Projects',
        'category' => CategoryType::PROJECTS,
        'raw_content' => 'Initial projects text',
        'is_active' => true,
    ]);

    $response = $this->put(route('cvwriter.knowledge.update', $file->id), [
        'title' => 'My Modern Projects',
        'category' => 'projects',
        'raw_content' => 'Modernized projects text',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('cvwriter.knowledge.index'));
    $response->assertSessionHas('success');

    $file->refresh();
    expect($file->title)->toBe('My Modern Projects');
    expect($file->raw_content)->toBe('Modernized projects text');

    // Chunks should reflect the new content
    expect($file->chunks->first()->content)->toBe('Modernized projects text');
});

test('authenticated users can delete a knowledge file and its chunks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Ai::fakeEmbeddings();

    $file = KnowledgeFile::create([
        'title' => 'To Be Deleted',
        'category' => CategoryType::BIO,
        'raw_content' => 'Delete me content',
        'is_active' => true,
    ]);

    // Ingest chunks first
    $file->chunks()->create([
        'chunk_index' => 0,
        'content' => 'Delete me content',
        'embedding' => array_fill(0, 768, 0.0),
    ]);

    $response = $this->delete(route('cvwriter.knowledge.destroy', $file->id));
    $response->assertRedirect(route('cvwriter.knowledge.index'));

    $this->assertDatabaseMissing('knowledge_files', ['id' => $file->id]);
    $this->assertDatabaseMissing('knowledge_chunks', ['knowledge_file_id' => $file->id]);
});

test('authenticated users can manually trigger re-ingestion', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Ai::fakeEmbeddings();

    $file = KnowledgeFile::create([
        'title' => 'Manually Reingested',
        'category' => CategoryType::BIO,
        'raw_content' => 'Some bio details',
        'is_active' => true,
    ]);

    $response = $this->post(route('cvwriter.knowledge.ingest', $file->id));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $file->refresh();
    expect($file->last_ingested_at)->not->toBeNull();
    expect($file->chunks)->not->toBeEmpty();
});
