<?php

namespace App\Models;

use App\Enum\ApprovalStatus;
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
        'template_id',
        'period_id',
        'created_by',
    ];

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(Borrower::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function summary(): HasOne
    {
        return $this->hasOne(ReportSummary::class);
    }

    public function reportAspects(): HasMany
    {
        return $this->hasMany(ReportAspect::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ReportApproval::class);
    }

    public function approvalHistory(): HasMany
    {
        return $this->hasMany(ApprovalHistory::class);
    }

    public function getCurrentApproval(): ?ReportApproval
    {
        return $this->approvals()
            ->where('status', ApprovalStatus::PENDING)
            ->orderBy('approval_step')
            ->first();
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
