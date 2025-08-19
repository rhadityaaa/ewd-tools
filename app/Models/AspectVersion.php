<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AspectVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'aspect_id',
        'version_number',
        'name',
        'description',
        'effective_from',
    ];

    protected $casts = [
        'effective_from' => 'datetime', 
    ];

    public function aspect(): BelongsTo
    {
        return $this->belongsTo(Aspect::class);
    }

    public function templates(): BelongsToMany
    {
        return $this->belongsToMany(TemplateVersion::class, 'aspect_template_versions')
                    ->withPivot('weight')
                    ->withTimestamps();
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
