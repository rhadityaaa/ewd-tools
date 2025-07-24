<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function templateVersions()
    {
        return $this->hasMany(TemplateVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(TemplateVersion::class)->latestOfMany();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
