<?php

namespace App\Models;

use App\Enum\ApprovalStatus;
use App\Enum\ReportStatus;
use App\Enum\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\ReportApproval;
use App\Models\ApprovalHistory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id',
        'period_id',
        'template_id',
        'status',
        'submitted_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'status' => ReportStatus::class,
        'submitted_at' => 'datetime',
    ];

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function summary(): HasOne
    {
        return $this->hasOne(ReportSummary::class);
    }

    public function aspects(): HasMany
    {
        return $this->hasMany(ReportAspect::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ReportApproval::class);
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function getCurrentApproval()
    {
        // Get the next pending approval in workflow order
        $workflowOrder = [
            WorkflowStep::RISK_ANALYST->value,
            WorkflowStep::KADEPT_BISNIS->value, 
            WorkflowStep::KADEPT_RISK->value,
        ];
        
        foreach ($workflowOrder as $stepValue) {
            $approval = $this->approvals()
                ->where('approval_step', $stepValue)
                ->where('status', ApprovalStatus::PENDING)
                ->first();
                
            if ($approval) {
                return $approval;
            }
        }
        
        return null;
    }

    public function getCurrentStep()
    {
        return $this->getCurrentApproval()?->approval_step;
    }

    public function getCurrentApprover()
    {
        return $this->getCurrentApproval()?->assigned;
    }

    public function isInApprovalProcess()
    {
        return $this->status->isInApprovalProcess();
    }
}
