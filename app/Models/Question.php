<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Question extends Model
{
    protected $fillable = [];
    
    public function questionVersions()
    {
        return $this->hasMany(QuestionVersion::class);
    }

    public function aspect()
    {
        return $this->belongsTo(Aspect::class);
    }

    public function visibilityRules(): MorphMany
    {
        return $this->morphMany(VisibilityRule::class, 'entity');
    }
}
