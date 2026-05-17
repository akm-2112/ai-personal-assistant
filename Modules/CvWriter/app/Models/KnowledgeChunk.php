<?php

namespace Modules\CvWriter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\CvWriter\Database\Factories\KnowledgeChunkFactory;

class KnowledgeChunk extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $table = 'knowledge_chunks';

    protected $fillable = [
        'knowledge_file_id',
        'chunk_index',
        'content',
        'embedding',
        'metadata',
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'embedding' => 'array',
        'metadata' => 'array',
    ];

    public function knowledgeFile(): BelongsTo
    {
        return $this->belongsTo(KnowledgeFile::class, 'knowledge_file_id');
    }

    // protected static function newFactory(): KnowledgeChunkFactory
    // {
    //     // return KnowledgeChunkFactory::new();
    // }
}
