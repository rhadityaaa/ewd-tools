<?php

namespace App\Models;

use App\Enum\ApprovalAction;
use App\Enum\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalHistory extends Model
{
    use HasFactory;

    protected $table = 'approval_histories';

    protected $fillable = [
        'report_id',
        'user_id',
        'action',
        'approval_step',
        'from_step',
        'to_step',
        'comments',
        'action_at',
        'metadata',
    ];

    protected $casts = [
        'action' => ApprovalAction::class,
        'approval_step' => WorkflowStep::class,
        'from_step' => WorkflowStep::class,
        'to_step' => WorkflowStep::class,
        'action_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
