<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function aspectVersion()
    {
        // return $this->belongsTo(AspectV::class);
    }
}
