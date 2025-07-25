<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aspect extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
    ];

    public function aspectVersions()
    {
        return $this->hasMany(AspectVersion::class);
    }

    public function latestAspectVersion()
    {
        return $this->hasOne(AspectVersion::class)->latestOfMany();
    }
}
