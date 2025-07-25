<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAspect extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'aspect_version_id',
        'total_score',
        'classification',
    ];

    protected $casts = [
        'total_score' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function aspectVersion(): BelongsTo
    {
        return $this->belongsTo(AspectVersion::class);
    }

    public function questionVersion()
    {
        return $this->belongsTo(QuestionVersion::class, 'question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
