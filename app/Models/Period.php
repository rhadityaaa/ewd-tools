<?php

namespace App\Models;

use App\Enum\Status;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'created_by',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'status' => Status::class,
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
