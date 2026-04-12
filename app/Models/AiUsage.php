<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    protected $fillable = [
        'ai_run_id',
        'model',
        'provider',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'input',
        'output',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'cost' => 'decimal:8',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(AiRun::class, 'ai_run_id');
    }
}
