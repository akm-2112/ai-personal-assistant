# CV Writer Agent — Implementation Plan (Part 1 of 2)

### Architecture, Database & Module Structure

---

## What We Are Building

A `CVWriter` nwidart module inside your Laravel 13 AI Personal Assistant project.

**The full flow:**

1. User pastes a job description
2. Agent reads it, uses RAG to search your knowledge files
3. Agent asks clarifying questions ONLY if the JD is genuinely unclear
4. Agent outputs a tailored CV + cover letter
5. User downloads as PDF

---

## Tech Stack for This Module

| Layer           | Choice                               | Reason                 |
| --------------- | ------------------------------------ | ---------------------- |
| Framework       | Laravel 13                           | Your current setup     |
| Module system   | nwidart/laravel-modules              | Your current setup     |
| AI SDK          | laravel/ai                           | Your current setup     |
| AI Provider     | Gemini (swappable)                   | Free, supported by SDK |
| Embedding model | Gemini text-embedding-004            | 768 dimensions, free   |
| Vector store    | pgvector on your existing PostgreSQL | No new service needed  |
| PDF generation  | barryvdh/laravel-dompdf              | Simple, PHP-native     |
| File storage    | Laravel local disk                   | Knowledge files        |

---

## Architecture Overview

```
User (Browser)
     │
     ▼
[CVWriterController]          [KnowledgeFileController]
     │                                  │
     │                         CRUD your .md/.txt files
     │                                  │
     ▼                                  ▼
[CVWriterAgent]              [KnowledgeIngestionService]
  Laravel AI SDK Agent          chunks + embeds files
     │                          stores in knowledge_chunks
     ├── Tool: SearchKnowledgeTool
     │         pgvector similarity search
     │
     ├── Tool: GetProfileSummaryTool
     │         returns structured profile data
     │
     └── Tool: FlagAmbiguityTool
               signals if JD needs clarification
     │
     ▼
[Structured JSON Output]
  CV sections + cover letter
     │
     ▼
[PdfExportService]
  renders Blade → PDF
     │
     ▼
Downloadable PDF
```

---

## Module Folder Structure

```
Modules/
└── CVWriter/
    ├── app/
    │   ├── Agents/
    │   │   └── CVWriterAgent.php
    │   ├── Tools/
    │   │   ├── SearchKnowledgeTool.php
    │   │   ├── GetProfileSummaryTool.php
    │   │   └── FlagAmbiguityTool.php
    │   ├── Http/
    │   │   └── Controllers/
    │   │       ├── CVWriterController.php
    │   │       └── KnowledgeFileController.php
    │   ├── Models/
    │   │   ├── KnowledgeFile.php
    │   │   ├── KnowledgeChunk.php
    │   │   ├── CvSession.php
    │   │   └── GeneratedCv.php
    │   ├── Services/
    │   │   ├── KnowledgeIngestionService.php
    │   │   └── PdfExportService.php
    │   └── Providers/
    │       └── CVWriterServiceProvider.php
    ├── database/
    │   └── migrations/
    │       ├── ..._create_knowledge_files_table.php
    │       ├── ..._create_knowledge_chunks_table.php
    │       ├── ..._create_cv_sessions_table.php
    │       └── ..._create_generated_cvs_table.php
    ├── resources/
    │   └── views/
    │       ├── index.blade.php
    │       ├── knowledge/
    │       │   ├── index.blade.php
    │       │   └── form.blade.php
    │       └── pdf/
    │           ├── cv.blade.php
    │           └── cover_letter.blade.php
    └── routes/
        └── web.php
```

---

## Database Tables

### Table 1: `knowledge_files`

Your raw files about yourself — skills, projects, experience, etc.

| Column           | Type         | Notes                                          |
| ---------------- | ------------ | ---------------------------------------------- |
| id               | bigint PK    | auto increment                                 |
| title            | varchar(255) | e.g. "My Projects", "Work Experience"          |
| category         | varchar(100) | e.g. `skills`, `experience`, `projects`, `bio` |
| raw_content      | longtext     | the actual markdown/text content               |
| is_active        | boolean      | default true — disable without deleting        |
| last_ingested_at | timestamp    | nullable — when chunks were last generated     |
| created_at       | timestamp    |                                                |
| updated_at       | timestamp    |                                                |

---

### Table 2: `knowledge_chunks`

Chunked + embedded pieces of your knowledge files. This is the RAG table.

| Column            | Type        | Notes                                      |
| ----------------- | ----------- | ------------------------------------------ |
| id                | bigint PK   |                                            |
| knowledge_file_id | bigint FK   | → knowledge_files.id (cascade delete)      |
| chunk_index       | integer     | order within the file (0, 1, 2...)         |
| content           | text        | chunk text, ~400 tokens each               |
| embedding         | vector(768) | pgvector column — Gemini embedding-004     |
| metadata          | jsonb       | `{"file_title": "...", "category": "..."}` |
| created_at        | timestamp   |                                            |
| updated_at        | timestamp   |                                            |

> **Note on dimensions:** Gemini `text-embedding-004` = 768 dims. If you switch to OpenAI `text-embedding-3-small` later, change to `vector(1536)`. The SDK swap is one line in config.

---

### Table 3: `cv_sessions`

One row per CV generation request.

| Column                  | Type        | Notes                                                       |
| ----------------------- | ----------- | ----------------------------------------------------------- |
| id                      | bigint PK   |                                                             |
| session_uuid            | uuid        | used in URLs instead of raw ID                              |
| job_description         | text        | raw JD the user pasted                                      |
| parsed_jd               | jsonb       | agent's parse: `{role, company, required_skills, tone}`     |
| clarification_questions | jsonb       | nullable — questions agent asked                            |
| user_answers            | jsonb       | nullable — user's answers                                   |
| retrieved_chunk_ids     | jsonb       | array of chunk IDs used in generation                       |
| status                  | varchar(50) | `pending` → `clarifying` → `generating` → `done` → `failed` |
| ai_provider             | varchar(50) | `gemini` — for audit log                                    |
| created_at              | timestamp   |                                                             |
| updated_at              | timestamp   |                                                             |

---

### Table 4: `generated_cvs`

The actual output for each session.

| Column        | Type         | Notes                                                     |
| ------------- | ------------ | --------------------------------------------------------- |
| id            | bigint PK    |                                                           |
| cv_session_id | bigint FK    | → cv_sessions.id                                          |
| cv_content    | jsonb        | full structured CV: summary, experience, skills, projects |
| cover_letter  | text         | plain text cover letter                                   |
| pdf_path      | varchar(500) | path to PDF file on disk                                  |
| version       | integer      | default 1 — increments on regeneration                    |
| created_at    | timestamp    |                                                           |

---

## Required Packages

| Package                       | Command                                    |
| ----------------------------- | ------------------------------------------ |
| laravel/ai                    | already installed                          |
| nwidart/laravel-modules       | already installed                          |
| barryvdh/laravel-dompdf       | `composer require barryvdh/laravel-dompdf` |
| pgvector PostgreSQL extension | see Step 1 in Part 2                       |

---

## Routes Plan

```
GET    /cv-writer                        → show main UI (paste JD here)
POST   /cv-writer/generate               → start generation
GET    /cv-writer/session/{uuid}         → poll session status
POST   /cv-writer/session/{uuid}/answer  → submit clarification answers
GET    /cv-writer/session/{uuid}/pdf     → download PDF

GET    /cv-writer/knowledge              → list all knowledge files
GET    /cv-writer/knowledge/create       → create form
POST   /cv-writer/knowledge              → store new file + trigger ingestion
GET    /cv-writer/knowledge/{id}/edit    → edit form
PUT    /cv-writer/knowledge/{id}         → update + re-ingest
DELETE /cv-writer/knowledge/{id}         → delete
POST   /cv-writer/knowledge/{id}/ingest  → manually re-trigger ingestion
```

---

## Data Flow: Step by Step

```
1. User opens /cv-writer/knowledge
   └── adds files about themselves (skills, projects, experience)
   └── each save triggers KnowledgeIngestionService
       └── chunks the content
       └── calls Ai::embed() for each chunk
       └── stores vectors in knowledge_chunks

2. User opens /cv-writer
   └── pastes job description → POST /cv-writer/generate
   └── creates cv_sessions row (status: pending)
   └── dispatches CVGenerationJob (queued)

3. CVGenerationJob runs
   └── prompts CVWriterAgent with the JD
   └── Agent calls SearchKnowledgeTool (RAG search)
   └── Agent calls GetProfileSummaryTool (full profile)
   └── Agent returns structured JSON

4a. If needs_clarification = true
    └── saves questions to cv_sessions
    └── status → clarifying
    └── UI shows questions to user
    └── user answers → POST /cv-writer/session/{uuid}/answer
    └── re-prompts agent with answers

4b. If needs_clarification = false
    └── goes straight to generation

5. Agent returns full structured CV + cover letter JSON
   └── saves to generated_cvs
   └── PdfExportService renders Blade template → PDF
   └── saves PDF to storage
   └── status → done

6. User downloads PDF from /cv-writer/session/{uuid}/pdf
```

---

_Part 2 covers: all code, migrations, step-by-step build instructions._

# CV Writer Agent — Implementation Plan (Part 2 of 2)

### Code, Migrations & Step-by-Step Build Instructions

---

## Step-by-Step Order

Follow this exact order. Do not skip ahead.

```
PHASE 1 — Environment Setup
  Step 1: Enable pgvector on PostgreSQL
  Step 2: Install required packages
  Step 3: Create the nwidart module

PHASE 2 — Database
  Step 4: Write and run all migrations

PHASE 3 — Models
  Step 5: Create all 4 models

PHASE 4 — Knowledge Management
  Step 6: KnowledgeIngestionService
  Step 7: KnowledgeFileController + routes + views

PHASE 5 — Agent
  Step 8: All 3 Tools
  Step 9: CVWriterAgent

PHASE 6 — CV Generation
  Step 10: CVWriterController
  Step 11: CVGenerationJob
  Step 12: PdfExportService + Blade templates

PHASE 7 — Wiring
  Step 13: Register module service provider
  Step 14: Test end to end
```

---

## PHASE 1 — Environment Setup

---

### Step 1: Enable pgvector on PostgreSQL

Run this once on your PostgreSQL database directly.

```sql
CREATE EXTENSION IF NOT EXISTS vector;
```

Verify it works:

```sql
SELECT * FROM pg_extension WHERE extname = 'vector';
```

You should see one row returned. If you get an error, your Postgres version
may not have pgvector installed. On Ubuntu:

```bash
sudo apt install postgresql-16-pgvector
# restart postgres
sudo systemctl restart postgresql
```

---

### Step 2: Install Required Packages

```bash
# PDF generation
composer require barryvdh/laravel-dompdf

# pgvector PHP helper (for casting vector columns)
composer require pgvector/pgvector
```

Publish dompdf config:

```bash
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

---

### Step 3: Create the nwidart Module

```bash
php artisan module:make CVWriter
```

This generates the full folder structure inside `Modules/CVWriter/`.

---

## PHASE 2 — Database

---

### Step 4: Migrations

Create each migration file inside `Modules/CVWriter/database/migrations/`.

---

#### Migration 1: knowledge_files

```php
<?php
// Modules/CVWriter/database/migrations/2025_01_01_000001_create_knowledge_files_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('category', 100)->default('general');
            // category examples: skills, experience, projects, bio, education
            $table->longText('raw_content');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_ingested_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_files');
    }
};
```

---

#### Migration 2: knowledge_chunks

```php
<?php
// Modules/CVWriter/database/migrations/2025_01_01_000002_create_knowledge_chunks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enable pgvector extension (safe to run again)
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_file_id')
                  ->constrained('knowledge_files')
                  ->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // Add pgvector column manually (Blueprint does not support vector type)
        // Gemini text-embedding-004 outputs 768 dimensions
        DB::statement(
            'ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(768)'
        );

        // Add HNSW index for fast similarity search
        DB::statement(
            'CREATE INDEX knowledge_chunks_embedding_idx
             ON knowledge_chunks
             USING hnsw (embedding vector_cosine_ops)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
```

---

#### Migration 3: cv_sessions

```php
<?php
// Modules/CVWriter/database/migrations/2025_01_01_000003_create_cv_sessions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cv_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_uuid')->unique();
            $table->text('job_description');
            $table->jsonb('parsed_jd')->nullable();
            // { role, company, required_skills[], tone }
            $table->jsonb('clarification_questions')->nullable();
            $table->jsonb('user_answers')->nullable();
            $table->jsonb('retrieved_chunk_ids')->nullable();
            $table->string('status', 50)->default('pending');
            // pending → clarifying → generating → done → failed
            $table->string('ai_provider', 50)->default('gemini');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cv_sessions');
    }
};
```

---

#### Migration 4: generated_cvs

```php
<?php
// Modules/CVWriter/database/migrations/2025_01_01_000004_create_generated_cvs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_cvs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cv_session_id')
                  ->constrained('cv_sessions')
                  ->cascadeOnDelete();
            $table->jsonb('cv_content');
            // { summary, experience[], skills[], projects[], education[] }
            $table->text('cover_letter');
            $table->string('pdf_path', 500)->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_cvs');
    }
};
```

Run all migrations:

```bash
php artisan module:migrate CVWriter
```

---

## PHASE 3 — Models

---

### Step 5: All 4 Models

---

#### KnowledgeFile.php

```php
<?php
// Modules/CVWriter/app/Models/KnowledgeFile.php

namespace Modules\CVWriter\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeFile extends Model
{
    protected $fillable = [
        'title',
        'category',
        'raw_content',
        'is_active',
        'last_ingested_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'last_ingested_at' => 'datetime',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    public function isIngested(): bool
    {
        return $this->last_ingested_at !== null;
    }
}
```

---

#### KnowledgeChunk.php

```php
<?php
// Modules/CVWriter/app/Models/KnowledgeChunk.php

namespace Modules\CVWriter\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'knowledge_file_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'metadata'  => 'array',
        'chunk_index' => 'integer',
    ];

    public function knowledgeFile(): BelongsTo
    {
        return $this->belongsTo(KnowledgeFile::class);
    }
}
```

---

#### CvSession.php

```php
<?php
// Modules/CVWriter/app/Models/CvSession.php

namespace Modules\CVWriter\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CvSession extends Model
{
    protected $fillable = [
        'session_uuid',
        'job_description',
        'parsed_jd',
        'clarification_questions',
        'user_answers',
        'retrieved_chunk_ids',
        'status',
        'ai_provider',
        'error_message',
    ];

    protected $casts = [
        'parsed_jd'                => 'array',
        'clarification_questions'  => 'array',
        'user_answers'             => 'array',
        'retrieved_chunk_ids'      => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (CvSession $session) {
            $session->session_uuid = Str::uuid();
        });
    }

    public function generatedCv(): HasOne
    {
        return $this->hasOne(GeneratedCv::class);
    }

    public function needsClarification(): bool
    {
        return $this->status === 'clarifying';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }
}
```

---

#### GeneratedCv.php

```php
<?php
// Modules/CVWriter/app/Models/GeneratedCv.php

namespace Modules\CVWriter\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedCv extends Model
{
    protected $fillable = [
        'cv_session_id',
        'cv_content',
        'cover_letter',
        'pdf_path',
        'version',
    ];

    protected $casts = [
        'cv_content' => 'array',
        'version'    => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CvSession::class, 'cv_session_id');
    }
}
```

---

## PHASE 4 — Knowledge Management

---

### Step 6: KnowledgeIngestionService

This is the core of your RAG pipeline. It chunks your files and embeds them.

```php
<?php
// Modules/CVWriter/app/Services/KnowledgeIngestionService.php

namespace Modules\CVWriter\App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Facades\Ai;
use Modules\CVWriter\App\Models\KnowledgeChunk;
use Modules\CVWriter\App\Models\KnowledgeFile;

class KnowledgeIngestionService
{
    // ~400 tokens per chunk (1 token ≈ 4 chars)
    const CHUNK_SIZE    = 1600;
    const CHUNK_OVERLAP = 200;

    public function ingest(KnowledgeFile $file): void
    {
        Log::info("Ingesting knowledge file: {$file->title}");

        // Delete old chunks for this file
        KnowledgeChunk::where('knowledge_file_id', $file->id)->delete();

        $chunks = $this->splitIntoChunks($file->raw_content);

        foreach ($chunks as $index => $chunkText) {
            // Get embedding from AI provider (Gemini text-embedding-004)
            $embedding = $this->embed($chunkText);

            // Insert using raw DB to handle the vector type
            DB::table('knowledge_chunks')->insert([
                'knowledge_file_id' => $file->id,
                'chunk_index'       => $index,
                'content'           => $chunkText,
                'embedding'         => $embedding,
                'metadata'          => json_encode([
                    'file_title' => $file->title,
                    'category'   => $file->category,
                ]),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $file->update(['last_ingested_at' => now()]);

        Log::info("Ingested {$file->title}: " . count($chunks) . " chunks created.");
    }

    public function searchSimilar(string $query, int $limit = 8): array
    {
        $queryEmbedding = $this->embed($query);

        // pgvector cosine similarity search
        $results = DB::select("
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
        ", [
            'embedding'  => $queryEmbedding,
            'embedding2' => $queryEmbedding,
            'limit'      => $limit,
        ]);

        return $results;
    }

    private function embed(string $text): string
    {
        // Laravel AI SDK embedding call
        // Returns a float array — we format it as a pgvector string
        $vector = Ai::embed($text);

        // Format: [0.123, 0.456, ...]
        return '[' . implode(',', $vector) . ']';
    }

    private function splitIntoChunks(string $text): array
    {
        $chunks = [];
        $length = strlen($text);
        $start  = 0;

        while ($start < $length) {
            $end = min($start + self::CHUNK_SIZE, $length);

            // Try to break at paragraph boundary to keep context clean
            if ($end < $length) {
                $slice      = substr($text, $start, $end - $start);
                $breakPoint = strrpos($slice, "\n\n");
                if ($breakPoint !== false && $breakPoint > self::CHUNK_SIZE / 2) {
                    $end = $start + $breakPoint;
                }
            }

            $chunk = trim(substr($text, $start, $end - $start));
            if (!empty($chunk)) {
                $chunks[] = $chunk;
            }

            $start = $end - self::CHUNK_OVERLAP;

            // Safety: avoid infinite loop on tiny texts
            if ($start >= $length) break;
        }

        return $chunks;
    }
}
```

---

### Step 7: KnowledgeFileController + Routes + Views

#### KnowledgeFileController.php

```php
<?php
// Modules/CVWriter/app/Http/Controllers/KnowledgeFileController.php

namespace Modules\CVWriter\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CVWriter\App\Models\KnowledgeFile;
use Modules\CVWriter\App\Services\KnowledgeIngestionService;

class KnowledgeFileController extends Controller
{
    public function __construct(
        private KnowledgeIngestionService $ingestion
    ) {}

    public function index()
    {
        $files = KnowledgeFile::latest()->get();
        return view('cvwriter::knowledge.index', compact('files'));
    }

    public function create()
    {
        return view('cvwriter::knowledge.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|string|max:100',
            'raw_content' => 'required|string',
        ]);

        $file = KnowledgeFile::create($data);

        // Ingest immediately after save
        $this->ingestion->ingest($file);

        return redirect()->route('cvwriter.knowledge.index')
                         ->with('success', "'{$file->title}' saved and ingested.");
    }

    public function edit(KnowledgeFile $knowledgeFile)
    {
        return view('cvwriter::knowledge.form', ['file' => $knowledgeFile]);
    }

    public function update(Request $request, KnowledgeFile $knowledgeFile)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'category'    => 'required|string|max:100',
            'raw_content' => 'required|string',
            'is_active'   => 'boolean',
        ]);

        $knowledgeFile->update($data);

        // Re-ingest because content changed
        $this->ingestion->ingest($knowledgeFile);

        return redirect()->route('cvwriter.knowledge.index')
                         ->with('success', "'{$knowledgeFile->title}' updated and re-ingested.");
    }

    public function destroy(KnowledgeFile $knowledgeFile)
    {
        $title = $knowledgeFile->title;
        $knowledgeFile->delete(); // chunks cascade delete

        return redirect()->route('cvwriter.knowledge.index')
                         ->with('success', "'{$title}' deleted.");
    }

    public function reIngest(KnowledgeFile $knowledgeFile)
    {
        $this->ingestion->ingest($knowledgeFile);

        return back()->with('success', "Re-ingested '{$knowledgeFile->title}'.");
    }
}
```

---

#### Routes (web.php)

```php
<?php
// Modules/CVWriter/routes/web.php

use Illuminate\Support\Facades\Route;
use Modules\CVWriter\App\Http\Controllers\CVWriterController;
use Modules\CVWriter\App\Http\Controllers\KnowledgeFileController;

Route::prefix('cv-writer')->name('cvwriter.')->group(function () {

    // Main CV generation UI
    Route::get('/', [CVWriterController::class, 'index'])->name('index');
    Route::post('/generate', [CVWriterController::class, 'generate'])->name('generate');
    Route::get('/session/{uuid}', [CVWriterController::class, 'session'])->name('session');
    Route::post('/session/{uuid}/answer', [CVWriterController::class, 'answer'])->name('answer');
    Route::get('/session/{uuid}/pdf', [CVWriterController::class, 'downloadPdf'])->name('pdf');

    // Knowledge file management
    Route::prefix('knowledge')->name('knowledge.')->group(function () {
        Route::get('/', [KnowledgeFileController::class, 'index'])->name('index');
        Route::get('/create', [KnowledgeFileController::class, 'create'])->name('create');
        Route::post('/', [KnowledgeFileController::class, 'store'])->name('store');
        Route::get('/{knowledgeFile}/edit', [KnowledgeFileController::class, 'edit'])->name('edit');
        Route::put('/{knowledgeFile}', [KnowledgeFileController::class, 'update'])->name('update');
        Route::delete('/{knowledgeFile}', [KnowledgeFileController::class, 'destroy'])->name('destroy');
        Route::post('/{knowledgeFile}/ingest', [KnowledgeFileController::class, 'reIngest'])->name('ingest');
    });
});
```

---

## PHASE 5 — Agent & Tools

---

### Step 8: The 3 Tools

#### SearchKnowledgeTool.php

```php
<?php
// Modules/CVWriter/app/Tools/SearchKnowledgeTool.php

namespace Modules\CVWriter\App\Tools;

use Laravel\Ai\Tool;
use Modules\CVWriter\App\Services\KnowledgeIngestionService;

class SearchKnowledgeTool extends Tool
{
    public string $name = 'search_knowledge';

    public string $description = 'Search the personal knowledge base using semantic search.
        Use this to find relevant skills, experience, projects, and background
        about the candidate that match the job description requirements.
        Call this multiple times with different queries to get comprehensive information.';

    public function __construct(
        private KnowledgeIngestionService $ingestion
    ) {}

    public function handle(string $query): string
    {
        $results = $this->ingestion->searchSimilar($query, limit: 8);

        if (empty($results)) {
            return "No relevant knowledge found for query: {$query}";
        }

        return collect($results)
            ->map(fn($r) => "[{$r->file_title}]\n{$r->content}")
            ->implode("\n\n---\n\n");
    }
}
```

---

#### GetProfileSummaryTool.php

```php
<?php
// Modules/CVWriter/app/Tools/GetProfileSummaryTool.php

namespace Modules\CVWriter\App\Tools;

use Laravel\Ai\Tool;
use Modules\CVWriter\App\Models\KnowledgeFile;

class GetProfileSummaryTool extends Tool
{
    public string $name = 'get_profile_summary';

    public string $description = 'Get a full structured summary of all active knowledge files
        about the candidate. Use this at the start to understand the full profile
        before doing targeted searches. Returns all file titles and categories.';

    public function handle(): string
    {
        $files = KnowledgeFile::where('is_active', true)
            ->select('title', 'category', 'raw_content', 'last_ingested_at')
            ->get();

        if ($files->isEmpty()) {
            return 'No knowledge files found. The knowledge base is empty.';
        }

        return $files->map(function ($file) {
            return "=== {$file->title} (category: {$file->category}) ===\n{$file->raw_content}";
        })->implode("\n\n");
    }
}
```

---

#### FlagAmbiguityTool.php

```php
<?php
// Modules/CVWriter/app/Tools/FlagAmbiguityTool.php

namespace Modules\CVWriter\App\Tools;

use Laravel\Ai\Tool;

class FlagAmbiguityTool extends Tool
{
    public string $name = 'flag_ambiguity';

    public string $description = 'Use this ONLY when the job description is genuinely unclear
        and you cannot proceed without more information. For example: missing tech stack,
        contradictory requirements, or completely missing role description.
        Do NOT use this for minor gaps you can reasonably infer.
        Pass an array of specific questions to ask the user.';

    public function handle(array $questions): array
    {
        // Just returns the questions — the controller handles the UI
        return $questions;
    }
}
```

---

### Step 9: CVWriterAgent.php

```php
<?php
// Modules/CVWriter/app/Agents/CVWriterAgent.php

namespace Modules\CVWriter\App\Agents;

use Laravel\Ai\Agent;
use Modules\CVWriter\App\Tools\FlagAmbiguityTool;
use Modules\CVWriter\App\Tools\GetProfileSummaryTool;
use Modules\CVWriter\App\Tools\SearchKnowledgeTool;

class CVWriterAgent extends Agent
{
    protected array $tools = [
        SearchKnowledgeTool::class,
        GetProfileSummaryTool::class,
        FlagAmbiguityTool::class,
    ];

    public function systemPrompt(): string
    {
        return <<<PROMPT
        You are an expert CV and cover letter writer. Your job is to write a
        tailored, professional CV and cover letter for a specific job description.

        PROCESS:
        1. Call get_profile_summary to understand the candidate's full background.
        2. Analyze the job description carefully: identify required skills, experience
           level, tech stack, company tone, and key responsibilities.
        3. Call search_knowledge with targeted queries to find the most relevant
           experience and projects for THIS specific job.
        4. ONLY call flag_ambiguity if the job description is so unclear that you
           genuinely cannot write a relevant CV. Minor gaps are fine — use your judgment.
        5. Write the CV and cover letter tailored to match the JD keywords and requirements.

        RULES:
        - Only include information found in the knowledge base. Never fabricate experience.
        - Match keywords from the job description in the CV summary and skills.
        - Prioritize the most relevant projects and skills for this specific role.
        - Cover letter should be professional, specific to the company/role, and concise.
        - Tone should match the company culture inferred from the JD.

        OUTPUT FORMAT — respond with valid JSON only:
        {
          "needs_clarification": false,
          "clarification_questions": [],
          "parsed_jd": {
            "role": "string",
            "company": "string or Unknown",
            "required_skills": ["skill1", "skill2"],
            "tone": "formal | startup | technical"
          },
          "cv": {
            "summary": "2-3 sentence professional summary tailored to this role",
            "experience": [
              {
                "title": "Job Title",
                "company": "Company Name",
                "period": "Month Year – Present",
                "bullets": ["achievement 1", "achievement 2"]
              }
            ],
            "skills": ["Skill 1", "Skill 2"],
            "projects": [
              {
                "name": "Project Name",
                "description": "One sentence description",
                "stack": ["Laravel", "React"],
                "url": "https://github.com/..."
              }
            ],
            "education": [
              {
                "degree": "Degree Name",
                "institution": "School Name",
                "year": "2024"
              }
            ]
          },
          "cover_letter": "Full cover letter text here"
        }

        If needs_clarification is true, populate clarification_questions and leave
        cv and cover_letter empty. The user will answer and you will be called again.
        PROMPT;
    }

    public function prompt(string $jobDescription, array $userAnswers = []): string
    {
        $prompt = "JOB DESCRIPTION:\n{$jobDescription}";

        if (!empty($userAnswers)) {
            $answersText = collect($userAnswers)
                ->map(fn($answer, $question) => "Q: {$question}\nA: {$answer}")
                ->implode("\n");
            $prompt .= "\n\nUSER CLARIFICATIONS:\n{$answersText}";
        }

        $prompt .= "\n\nPlease write a tailored CV and cover letter for this role.";

        return $prompt;
    }
}
```

---

## PHASE 6 — CV Generation

---

### Step 10: CVWriterController.php

```php
<?php
// Modules/CVWriter/app/Http/Controllers/CVWriterController.php

namespace Modules\CVWriter\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CVWriter\App\Jobs\CVGenerationJob;
use Modules\CVWriter\App\Models\CvSession;
use Modules\CVWriter\App\Models\GeneratedCv;
use Symfony\Component\HttpFoundation\Response;

class CVWriterController extends Controller
{
    public function index()
    {
        $recentSessions = CvSession::latest()->limit(10)->get();
        return view('cvwriter::index', compact('recentSessions'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'job_description' => 'required|string|min:50',
        ]);

        $session = CvSession::create([
            'job_description' => $request->job_description,
            'status'          => 'pending',
        ]);

        // Dispatch the agent job to the queue
        CVGenerationJob::dispatch($session);

        return redirect()->route('cvwriter.session', $session->session_uuid);
    }

    public function session(string $uuid)
    {
        $session = CvSession::where('session_uuid', $uuid)
            ->with('generatedCv')
            ->firstOrFail();

        return view('cvwriter::session', compact('session'));
    }

    public function answer(Request $request, string $uuid)
    {
        $session = CvSession::where('session_uuid', $uuid)->firstOrFail();

        $request->validate([
            'answers' => 'required|array',
        ]);

        $session->update([
            'user_answers' => $request->answers,
            'status'       => 'pending',
        ]);

        // Re-dispatch the agent with user answers
        CVGenerationJob::dispatch($session);

        return redirect()->route('cvwriter.session', $uuid);
    }

    public function downloadPdf(string $uuid)
    {
        $session = CvSession::where('session_uuid', $uuid)
            ->with('generatedCv')
            ->firstOrFail();

        abort_unless($session->isDone() && $session->generatedCv?->pdf_path, 404);

        $path = storage_path("app/{$session->generatedCv->pdf_path}");

        abort_unless(file_exists($path), 404);

        return response()->download($path, 'CV_' . now()->format('Y_m_d') . '.pdf');
    }
}
```

---

### Step 11: CVGenerationJob.php

```php
<?php
// Modules/CVWriter/app/Jobs/CVGenerationJob.php

namespace Modules\CVWriter\App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\CVWriter\App\Agents\CVWriterAgent;
use Modules\CVWriter\App\Models\CvSession;
use Modules\CVWriter\App\Models\GeneratedCv;
use Modules\CVWriter\App\Services\PdfExportService;

class CVGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(private CvSession $session) {}

    public function handle(CVWriterAgent $agent, PdfExportService $pdf): void
    {
        try {
            $this->session->update(['status' => 'generating']);

            $prompt   = $agent->prompt(
                $this->session->job_description,
                $this->session->user_answers ?? []
            );

            // Run the agent
            $response = $agent->run($prompt);

            // Parse structured JSON output
            $output = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Agent returned invalid JSON: ' . $response);
            }

            // Agent needs clarification
            if ($output['needs_clarification'] === true) {
                $this->session->update([
                    'status'                   => 'clarifying',
                    'parsed_jd'                => $output['parsed_jd'] ?? null,
                    'clarification_questions'  => $output['clarification_questions'],
                ]);
                return;
            }

            // Save parsed JD analysis
            $this->session->update([
                'parsed_jd' => $output['parsed_jd'] ?? null,
            ]);

            // Generate PDF
            $pdfPath = $pdf->generate($output['cv'], $output['cover_letter']);

            // Save the generated CV
            $existing = GeneratedCv::where('cv_session_id', $this->session->id)->first();
            $version  = $existing ? $existing->version + 1 : 1;

            GeneratedCv::updateOrCreate(
                ['cv_session_id' => $this->session->id],
                [
                    'cv_content'    => $output['cv'],
                    'cover_letter'  => $output['cover_letter'],
                    'pdf_path'      => $pdfPath,
                    'version'       => $version,
                ]
            );

            $this->session->update(['status' => 'done']);

        } catch (\Throwable $e) {
            Log::error('CVGenerationJob failed', [
                'session_id' => $this->session->id,
                'error'      => $e->getMessage(),
            ]);
            $this->session->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}
```

---

### Step 12: PdfExportService.php

```php
<?php
// Modules/CVWriter/app/Services/PdfExportService.php

namespace Modules\CVWriter\App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    public function generate(array $cvContent, string $coverLetter): string
    {
        // Render CV PDF
        $cvPdf = Pdf::loadView('cvwriter::pdf.cv', [
            'cv'           => $cvContent,
            'cover_letter' => $coverLetter,
        ]);

        $cvPdf->setPaper('a4', 'portrait');

        $filename = 'cv_' . now()->format('Y_m_d_His') . '_' . uniqid() . '.pdf';
        $path     = 'cv_exports/' . $filename;

        Storage::put($path, $cvPdf->output());

        return $path;
    }
}
```

---

#### PDF Blade Template

```blade
{{-- Modules/CVWriter/resources/views/pdf/cv.blade.php --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; padding: 30px; }

  .header { border-bottom: 3px solid #1E5FA8; padding-bottom: 12px; margin-bottom: 20px; }
  .name { font-size: 26px; font-weight: bold; color: #1E5FA8; }
  .tagline { font-size: 12px; color: #555; margin-top: 4px; }
  .links { font-size: 10px; color: #777; margin-top: 4px; }

  .section { margin-bottom: 18px; }
  .section-title { font-size: 13px; font-weight: bold; color: #1E5FA8; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; }

  .summary { color: #333; line-height: 1.6; }

  .job { margin-bottom: 12px; }
  .job-header { display: flex; justify-content: space-between; }
  .job-title { font-weight: bold; color: #1a1a1a; }
  .job-period { color: #777; font-size: 10px; }
  .job-company { color: #555; font-size: 10px; margin-bottom: 4px; }
  .job ul { padding-left: 14px; }
  .job li { margin-bottom: 2px; color: #333; line-height: 1.5; }

  .skills-grid { display: flex; flex-wrap: wrap; gap: 4px; }
  .skill-tag { background: #D6E4F7; color: #1E5FA8; padding: 2px 8px; border-radius: 3px; font-size: 10px; }

  .project { margin-bottom: 10px; }
  .project-name { font-weight: bold; color: #1a1a1a; }
  .project-stack { color: #1E5FA8; font-size: 10px; }
  .project-desc { color: #555; margin-top: 2px; }
  .project-url { color: #1E5FA8; font-size: 10px; }

  .cover-letter { page-break-before: always; padding-top: 20px; }
  .cover-letter p { line-height: 1.7; margin-bottom: 12px; color: #333; }
</style>
</head>
<body>

  {{-- HEADER --}}
  <div class="header">
    <div class="name">Aung Kyaw Moe</div>
    <div class="tagline">Full Stack Developer · Laravel · React · AI-Integrated Apps</div>
    <div class="links">aungkyawmoe.vercel.app · github.com/akm-2112 · Southeast Asia</div>
  </div>

  {{-- SUMMARY --}}
  <div class="section">
    <div class="section-title">Professional Summary</div>
    <div class="summary">{{ $cv['summary'] }}</div>
  </div>

  {{-- SKILLS --}}
  @if(!empty($cv['skills']))
  <div class="section">
    <div class="section-title">Skills</div>
    <div class="skills-grid">
      @foreach($cv['skills'] as $skill)
        <span class="skill-tag">{{ $skill }}</span>
      @endforeach
    </div>
  </div>
  @endif

  {{-- EXPERIENCE --}}
  @if(!empty($cv['experience']))
  <div class="section">
    <div class="section-title">Experience</div>
    @foreach($cv['experience'] as $exp)
    <div class="job">
      <div class="job-header">
        <span class="job-title">{{ $exp['title'] }}</span>
        <span class="job-period">{{ $exp['period'] }}</span>
      </div>
      <div class="job-company">{{ $exp['company'] }}</div>
      <ul>
        @foreach($exp['bullets'] as $bullet)
          <li>{{ $bullet }}</li>
        @endforeach
      </ul>
    </div>
    @endforeach
  </div>
  @endif

  {{-- PROJECTS --}}
  @if(!empty($cv['projects']))
  <div class="section">
    <div class="section-title">Projects</div>
    @foreach($cv['projects'] as $project)
    <div class="project">
      <span class="project-name">{{ $project['name'] }}</span>
      @if(!empty($project['stack']))
        <span class="project-stack"> · {{ implode(', ', $project['stack']) }}</span>
      @endif
      <div class="project-desc">{{ $project['description'] }}</div>
      @if(!empty($project['url']))
        <div class="project-url">{{ $project['url'] }}</div>
      @endif
    </div>
    @endforeach
  </div>
  @endif

  {{-- EDUCATION --}}
  @if(!empty($cv['education']))
  <div class="section">
    <div class="section-title">Education</div>
    @foreach($cv['education'] as $edu)
      <div><strong>{{ $edu['degree'] }}</strong> — {{ $edu['institution'] }} ({{ $edu['year'] }})</div>
    @endforeach
  </div>
  @endif

  {{-- COVER LETTER --}}
  <div class="cover-letter">
    <div class="section-title">Cover Letter</div>
    @foreach(explode("\n\n", $cover_letter) as $paragraph)
      <p>{{ $paragraph }}</p>
    @endforeach
  </div>

</body>
</html>
```

---

## PHASE 7 — Wiring It Together

---

### Step 13: Register the Module

Make sure your `CVWriterServiceProvider` registers the module routes and views properly. nwidart auto-generates this but verify it looks like this:

```php
<?php
// Modules/CVWriter/app/Providers/CVWriterServiceProvider.php

namespace Modules\CVWriter\App\Providers;

use Illuminate\Support\ServiceProvider;

class CVWriterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('CVWriter', 'database/migrations'));
        $this->loadViewsFrom(module_path('CVWriter', 'resources/views'), 'cvwriter');
        $this->loadRoutesFrom(module_path('CVWriter', 'routes/web.php'));
    }
}
```

Make sure it is listed in `config/modules.php` or `modules_statuses.json`:

```json
{
  "ExpenseTracker": true,
  "CVWriter": true
}
```

---

### Step 14: Queue Setup

The agent runs in a queued job. Make sure your queue is running:

```bash
# In development
php artisan queue:work

# In production use supervisor or Laravel Horizon
```

In your `.env`:

```env
QUEUE_CONNECTION=database
# or redis if you have it set up
```

---

## Step 15: End-to-End Test Checklist

Do these in order to verify everything works:

```
[ ] 1. php artisan module:migrate CVWriter
       Verify all 4 tables created in postgres

[ ] 2. Go to /cv-writer/knowledge/create
       Add a knowledge file (paste your skills as markdown)
       Save → check knowledge_chunks table has rows with embeddings

[ ] 3. Add 2-3 more knowledge files (projects, experience, bio)

[ ] 4. Go to /cv-writer
       Paste a real job description
       Submit

[ ] 5. Check cv_sessions table — status should move: pending → generating → done

[ ] 6. Check generated_cvs table — cv_content and cover_letter should be populated

[ ] 7. Download PDF from /cv-writer/session/{uuid}/pdf
       Verify PDF renders correctly

[ ] 8. Test clarification: paste a very vague JD
       Agent should ask questions instead of generating
       Answer questions → re-submit → check it generates after
```

---

## Knowledge File Examples (Add These First)

When you first set up the module, create these files in `/cv-writer/knowledge`:

**File 1 — title: "Tech Stack & Skills" | category: skills**

```
## Backend
Laravel (PHP), RESTful API design, Laravel Sanctum, Laravel AI SDK, MySQL, PostgreSQL

## Frontend
React.js, JavaScript ES6+, Tailwind CSS, Vite, HTML5, CSS3, Blade templating

## AI & Agents
Laravel AI SDK, AI agent architecture, LLM integration, RAG systems, pgvector

## Tools
Git, GitHub, Vercel, Postman, Composer, NPM, VS Code
```

**File 2 — title: "Work Experience" | category: experience**

```
## Full Stack Developer — [Company Name]
[Start Date] – Present | Southeast Asia

- Sole developer responsible for two live production web platforms
- Bus Booking System: cloned legacy platform, added new features, modernized UI for new operator
- Hotel Booking System: inherited 7-year-old dormant codebase, full modernization solo
- Direct client communication and requirement translation
- AI-driven workflow with no senior mentorship
```

**File 3 — title: "Projects" | category: projects**

```
## BayyKinn — Disaster Safety Platform
Code2Career Hackathon 2025 | Backend Developer
GitHub: github.com/akm-2112/BayyKinn

Disaster safety platform: real-time family location sharing, SOS alerts, safety status.
Built Laravel RESTful API with Sanctum auth. Integrated with Flutter mobile app and React admin dashboard.
Delivered in 48 hours, 6-person team.

## AI Personal Assistant
Ongoing Personal Project
GitHub: github.com/akm-2112/ai-personal-assistant

Testing Laravel AI SDK with agent architecture. Built expense tracker agent.
Building CV Writer Agent (RAG-powered) as next module.
```

---

## Summary: Build Order

| Step | What                                     | Where                             |
| ---- | ---------------------------------------- | --------------------------------- |
| 1    | Enable pgvector                          | PostgreSQL                        |
| 2    | Install packages                         | Terminal                          |
| 3    | Create module                            | Terminal                          |
| 4    | Write + run migrations                   | Modules/CVWriter/database         |
| 5    | Create 4 models                          | Modules/CVWriter/app/Models       |
| 6    | KnowledgeIngestionService                | Modules/CVWriter/app/Services     |
| 7    | KnowledgeFileController + routes + views | Modules/CVWriter/app/Http         |
| 8    | 3 Tools                                  | Modules/CVWriter/app/Tools        |
| 9    | CVWriterAgent                            | Modules/CVWriter/app/Agents       |
| 10   | CVWriterController                       | Modules/CVWriter/app/Http         |
| 11   | CVGenerationJob                          | Modules/CVWriter/app/Jobs         |
| 12   | PdfExportService + Blade PDF template    | Modules/CVWriter/app/Services     |
| 13   | Register service provider                | Providers + modules_statuses.json |
| 14   | Start queue worker                       | Terminal                          |
| 15   | End-to-end test                          | Browser                           |
