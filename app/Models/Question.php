<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'aspect_id',
        'question_text',
        'weight',
        'max_score',
        'min_score',
        'is_mandatory',
    ];

    public function aspect()
    {
        return $this->belongsTo(Aspect::class);
    }

    public function questionVersions()
    {
        return $this->hasMany(QuestionVersion::class);
    }

    public function visibilityRules()
    {
        return $this->morphMany(VisibilityRule::class, 'entity');
    }
}
