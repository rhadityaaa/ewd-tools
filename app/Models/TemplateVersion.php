<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'version_number',
        'description',
        'effective_from',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function aspectVersions()
    {
        return $this->belongsToMany(AspectVersion::class, 'aspect_template_versions')
                    ->withPivot('weight')
                    ->withTimestamps();
    }

    public function visibilityRules()
    {
        return $this->morphMany(VisibilityRule::class, 'entity');
    }
}
