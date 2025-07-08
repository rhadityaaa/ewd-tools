<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisibilityRule extends Model
{
    use HasFactory;

    protected $table = 'visibilty_rules';

    protected $fillable = [
        'entity_type',
        'entity_id', 
        'description',
        'source_type',
        'source_field',
        'operator',
        'value'
    ];

    public function entity()
    {
        return $this->morphTo();
    }
}
