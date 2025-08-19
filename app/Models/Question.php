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

    // Remove the aspect() relationship since it's not direct
    // The relationship is through question_versions -> aspect_versions
    
    public function visibilityRules(): MorphMany
    {
        return $this->morphMany(VisibilityRule::class, 'entity');
    }
}
