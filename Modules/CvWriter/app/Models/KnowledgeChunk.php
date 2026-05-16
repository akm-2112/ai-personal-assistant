<?php

namespace Modules\CvWriter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\CvWriter\Database\Factories\KnowledgeChunkFactory;

class KnowledgeChunk extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): KnowledgeChunkFactory
    // {
    //     // return KnowledgeChunkFactory::new();
    // }
}
