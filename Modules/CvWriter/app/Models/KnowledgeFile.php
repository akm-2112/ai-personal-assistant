<?php

namespace Modules\CvWriter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CvWriter\Enums\CategoryType;

// use Modules\CvWriter\Database\Factories\KnowledgeFileFactory;

class KnowledgeFile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'raw_content',
        'category',
        'is_active',
        'last_ingested_at',
    ];

    protected $casts = [
        'category' => CategoryType::class,
        'is_active' => 'boolean',
        'last_ingested_at' => 'datetime',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isIngested(): bool
    {
        return $this->last_ingested_at !== null;
    }

    // protected static function newFactory(): KnowledgeFileFactory
    // {
    //     // return KnowledgeFileFactory::new();
    // }
}
