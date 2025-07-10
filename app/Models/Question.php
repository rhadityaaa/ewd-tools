<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [];

    public function aspect()
    {
        return $this->belongsTo(Aspect::class);
    }

    public function questionVersions()
    {
        return $this->hasMany(QuestionVersion::class);
    }
}
