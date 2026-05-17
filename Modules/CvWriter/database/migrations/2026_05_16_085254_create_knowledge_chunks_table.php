<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_file_id')->constrained('knowledge_files')->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(768);');
        DB::statement('CREATE INDEX knowledge_chunks_embedding_hnsw_idx ON knowledge_chunks USING hnsw (embedding vector_cosine_ops);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
