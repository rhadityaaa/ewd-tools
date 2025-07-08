<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function users() 
    {
        return $this->hasMany(User::class, 'division_id');
    }

    public function borrowers()
    {
        return $this->hasMany(Borrower::class, 'division_id');
    }
}
