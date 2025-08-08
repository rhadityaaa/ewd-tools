<?php

namespace App\Services;

use App\Enum\ApprovalAction;
use App\Enum\ApprovalStatus;
use App\Enum\ReportStatus;
use App\Enum\WorkflowStep;
use App\Models\ApprovalHistory;
use App\Models\Report;
use App\Models\ReportApproval;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowService
{
    public function submitReport(Report $report, User $user, array $approvers = []): void
    {
        DB::transaction(function () use ($report, $user, $approvers) {
            $report->update([
                'status' => ReportStatus::SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->createApprovalSteps($report, $approvers);
            $this->logAction($report, $user, ApprovalAction::SUBMIT);
        });
    }

    public function approveStep(Report $report, User $user, string $comments = null): void
    {
        DB::transaction(function () use ($report, $user, $comments) {
            $currentApproval = $report->getCurrentApproval();
            
            if (!$currentApproval || $currentApproval->assigned_to !== $user->id) {
                throw new Exception('User tidak memiliki akses untuk menyetujui langkah ini.');
            }

            $currentApproval->update([
                'status' => ApprovalStatus::APPROVED,
            ]);

            $this->logAction($report, $user, ApprovalAction::APPROVE, $currentApproval->approval_step, $comments);

            // Check if this is the final step
            if ($currentApproval->approval_step === WorkflowStep::KADEPT_RISK) {
                $report->update(['status' => ReportStatus::APPROVED]);
            } else {
                $report->update(['status' => ReportStatus::UNDER_REVIEW]);
            }
        });
    }

    public function rejectStep(Report $report, User $user, string $comments): void
    {
        DB::transaction(function () use ($report, $user, $comments) {
            $currentApproval = $report->getCurrentApproval();
            
            if (!$currentApproval || $currentApproval->assigned_to !== $user->id) {
                throw new Exception('User tidak memiliki akses untuk menolak langkah ini.');
            }

            $currentApproval->update([
                'status' => ApprovalStatus::REJECTED,
            ]);

            $report->update([
                'status' => ReportStatus::REJECTED,
                'rejection_reason' => $comments,
            ]);

            $this->logAction($report, $user, ApprovalAction::REJECT, $currentApproval->approval_step, $comments);
        });
    }

    public function requestRevision(Report $report, User $user, string $comments): void
    {
        DB::transaction(function () use ($report, $user, $comments) {
            $currentApproval = $report->getCurrentApproval();
            
            if (!$currentApproval || $currentApproval->assigned_to !== $user->id) {
                throw new Exception('User tidak memiliki akses untuk meminta revisi.');
            }

            $report->update([
                'status' => ReportStatus::REVISION_REQUIRED,
                'rejection_reason' => $comments,
            ]);

            // Reset all approval steps
            $report->approvals()->delete();

            $this->logAction($report, $user, ApprovalAction::REVISION, $currentApproval->approval_step, $comments);
        });
    }

    public function overrideApproval(Report $report, User $user, string $comments): void
    {
        if (!$this->canOverride($user, $report->getCurrentStep())) {
            throw new Exception('User tidak memiliki akses untuk override approval.');
        }

        DB::transaction(function () use ($report, $user, $comments) {
            $report->update(['status' => ReportStatus::APPROVED]);
            
            // Mark all pending approvals as approved
            $report->approvals()
                ->where('status', ApprovalStatus::PENDING)
                ->update(['status' => ApprovalStatus::APPROVED]);

            $this->logAction($report, $user, ApprovalAction::OVERRIDE, null, $comments);
        });
    }

    public function reassignApproval(Report $report, User $fromUser, User $toUser, WorkflowStep $step, string $comments = null): void
    {
        if (!$this->canReassign($fromUser, $step)) {
            throw new Exception('User tidak memiliki akses untuk reassign approval.');
        }

        DB::transaction(function () use ($report, $fromUser, $toUser, $step, $comments) {
            $approval = $report->approvals()
                ->where('approval_step', $step)
                ->where('status', ApprovalStatus::PENDING)
                ->first();

            if (!$approval) {
                throw new Exception('Langkah approval tidak ditemukan atau sudah selesai.');
            }

            $approval->update([
                'assigned_to' => $toUser->id,
                'assigned_at' => now(),
            ]);

            $this->logAction($report, $fromUser, ApprovalAction::REASSIGN, $step, $comments, [
                'reassigned_to' => $toUser->id,
                'reassigned_to_name' => $toUser->name,
            ]);
        });
    }

    public function withdrawReport(Report $report, User $user): void
    {
        if (!$this->canWithdraw($user, $report)) {
            throw new Exception('User tidak memiliki akses untuk withdraw laporan.');
        }

        DB::transaction(function () use ($report, $user) {
            $report->update(['status' => ReportStatus::DRAFT]);
            
            // Delete all approval steps
            $report->approvals()->delete();

            $this->logAction($report, $user, ApprovalAction::WITHDRAW);
        });
    }

    public function getApprovalProgress(Report $report): array
    {
        $approvals = $report->approvals()->with('assigned')->get();
        
        return $approvals->map(function ($approval) {
            return [
                'step' => $approval->approval_step,
                'step_name' => WorkflowStep::labels()[$approval->approval_step->value],
                'status' => $approval->status,
                'assigned_to' => $approval->assigned,
                'assigned_at' => $approval->assigned_at,
                'is_current' => $approval->isPending(),
            ];
        })->toArray();
    }

    public function getApprovalHistory(Report $report): array
    {
        return $report->approvalHistory()
            ->with('user')
            ->orderBy('action_at', 'desc')
            ->get()
            ->map(function ($history) {
                return [
                    'action' => $history->action,
                    'user' => $history->user,
                    'step' => $history->approval_step,
                    'comments' => $history->comments,
                    'action_at' => $history->action_at,
                    'metadata' => $history->metadata,
                ];
            })->toArray();
    }

    private function createApprovalSteps(Report $report, array $approvers = []): void
    {
        $steps = [
            WorkflowStep::UNIT_BISNIS,
            WorkflowStep::RISK_ANALYST,
            WorkflowStep::KADEPT_BISNIS,
            WorkflowStep::KADEPT_RISK,
        ];

        foreach ($steps as $step) {
            $assignedUserId = $approvers[$step->value] ?? $this->getDefaultAssigned($step)->id;
            
            ReportApproval::create([
                'report_id' => $report->id,
                'approval_step' => $step,
                'status' => ApprovalStatus::PENDING,
                'assigned_to' => $assignedUserId,
                'assigned_at' => now(),
            ]);
        }
    }

    private function getDefaultAssigned(WorkflowStep $step): User
    {
        $role = $step->getRequiredRole();
        
        // Ambil user pertama dengan role yang sesuai sebagai fallback
        return User::role($role)
            ->orderBy('created_at', 'asc')
            ->firstOrFail();
    }

    private function logAction(Report $report, User $user, ApprovalAction $action, ?WorkflowStep $step = null, ?string $comments = null, array $metadata = []): void
    {
        ApprovalHistory::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'action' => $action,
            'approval_step' => $step,
            'comments' => $comments,
            'action_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function canOverride(User $user, ?WorkflowStep $currentStep): bool
    {
        // Hanya Kadept Bisnis dan Kadept Risk yang bisa override
        return $user->hasAnyRole(['kadept_bisnis', 'kadept_risk']) && 
               $currentStep && $currentStep->canOverride();
    }

    private function canReassign(User $user, WorkflowStep $step): bool
    {
        // User bisa reassign jika dia adalah approver saat ini atau supervisor
        return $user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin']);
    }

    private function canWithdraw(User $user, Report $report): bool
    {
        // Hanya pembuat laporan atau supervisor yang bisa withdraw
        return $report->created_by === $user->id || 
               $user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin']);
    }
}