<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'question_version_id',
        'question_option_id',
        'notes',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function questionVersion()
    {
        return $this->belongsTo(QuestionVersion::class);
    }

    public function questionOption()
    {
        return $this->belongsTo(QuestionOption::class);
    }
}