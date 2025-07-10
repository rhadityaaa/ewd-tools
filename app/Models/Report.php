<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id',
        'template_id',
        'period_id',
        'created_by',
    ];

    public function borrower()
    {
        return $this->belongsTo(Borrower::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function summary()
    {
        return $this->hasOne(ReportSummary::class);
    }

    public function aspects()
    {
        return $this->hasMany(ReportAspect::class);
    }

}
