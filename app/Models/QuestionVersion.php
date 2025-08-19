<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class QuestionVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'aspect_version_id',
        'version_number',
        'question_text',
        'weight',
        'max_score',
        'min_score',
        'is_mandatory',
        'effective_from'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'min_score' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'effective_from' => 'datetime'
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function aspectVersion(): BelongsTo
    {
        return $this->belongsTo(AspectVersion::class);
    }

    public function questionOptions(): HasMany
    {
        return $this->hasMany(QuestionOption::class);
    }

    public function visibilityRules(): MorphMany
    {
        return $this->morphMany(VisibilityRule::class, 'entity');
    }
}
