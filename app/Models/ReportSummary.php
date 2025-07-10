<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'final_classification',
        'indicative_collectibility',
        'is_override',
        'override_reason',
        'business_notes',
        'reviewer_notes',
    ];

    protected $casts = [
        'is_override' => 'boolean',
        'indicative_collectibility' => 'integer',
        'summary_generated_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
