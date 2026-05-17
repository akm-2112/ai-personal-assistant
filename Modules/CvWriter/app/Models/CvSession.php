<?php

namespace Modules\CvWriter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// use Modules\CvWriter\Database\Factories\CvSessionFactory;

class CvSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
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
        'cv_content',
        'cover_letter',
        'pdf_path',
        'version',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'parsed_jd' => 'array',
        'clarification_questions' => 'array',
        'user_answers' => 'array',
        'retrieved_chunk_ids' => 'array',
        'cv_content' => 'array',
        'version' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (CvSession $session) {
            if (empty($session->session_uuid)) {
                $session->session_uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Determine if the session needs clarification.
     */
    public function needsClarification(): bool
    {
        return $this->status === 'clarifying';
    }

    /**
     * Determine if the session is done.
     */
    public function isDone(): bool
    {
        return $this->status === 'done';
    }
}
