<?php

namespace App\Models;

use App\Enum\ApprovalStatus;
use App\Enum\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'approval_step',
        'status',
        'assigned_to',
        'assigned_at',
    ];

    protected $casts = [
        'approval_step' => WorkflowStep::class,
        'status' => ApprovalStatus::class,
        'assigned_at' => 'datetime',
    ];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function isPending()
    {
        return $this->status === ApprovalStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === ApprovalStatus::APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === ApprovalStatus::REJECTED;
    }
}