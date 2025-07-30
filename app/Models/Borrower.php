<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Borrower extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'division_id',
        'status',
        'inactive_reason',
        'inactive_date'
    ];

    protected $casts = [
        'inactive_date' => 'date',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function details(): HasOne
    {
        return $this->hasOne(BorrowerDetail::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(BorrowerFacility::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
