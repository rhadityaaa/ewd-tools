<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function latestVersion()
    {
        return $this->hasOne(TemplateVersion::class)->latestOfMany('version_number');
    }
}
