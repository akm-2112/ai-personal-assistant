<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cv_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_uuid')->unique();
            $table->text('job_description');
            $table->jsonb('parsed_jd')->nullable();
            $table->jsonb('clarification_questions')->nullable();
            $table->jsonb('user_answers')->nullable();
            $table->jsonb('retrieved_chunk_ids')->nullable();
            $table->string('status', 50)->default('pending');
            $table->string('ai_provider', 50)->default('gemini');
            $table->text('error_message')->nullable();

            // Merged from generated_cvs
            $table->jsonb('cv_content')->nullable();
            $table->text('cover_letter')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->integer('version')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cv_sessions');
    }
};
