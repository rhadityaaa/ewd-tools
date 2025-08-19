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
use App\Notifications\ReportRejectedNotification;
use App\Notifications\ReportSubmittedNotification;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowService
{
    public function submitReport(Report $report, User $user, array $approvers = []): void
    {
        if ($report->created_by !== $user->id) {
            throw new Exception('Hanya pembuat laporan yang dapat mengirim laporan.');
        }

        if (!in_array($report->status, [ReportStatus::DRAFT, ReportStatus::REJECTED])) {
            throw new Exception('Laporan hanya dapat dikirimkan dari status draft atau rejected.');
        }

        DB::transaction(function () use ($report, $user, $approvers) {
            $report->update([
                'status' => ReportStatus::SUBMITTED,
                'submitted_at' => now(),
                'rejection_reason' => null,
            ]);

            // To-do
        });
    }

    private function createFirstApprovalSteps(Report $report): void
    {
        $firstApprover = $this->getDefaultAssigned(WorkflowStep::RISK_ANALYST);

        ReportApproval::create([
            'report_id' => $report->id,
            'approval_step' => WorkflowStep::RISK_ANALYST,
            'status' => ApprovalStatus::PENDING,
            'assigned_to' => $firstApprover->id,
            'assigned_at' => now()
        ]);
    }

    public function approveStep(Report $report, User $user, ?string $comments = null): void
    {
        DB::transaction(function () use ($report, $user, $comments) {
            $currentApproval = $this->getCurrentApproval($report);

            $this->validateApprovalPermission($currentApproval, $user);

            $currentApproval->update(['status' => ApprovalStatus::APPROVED]);

            $this->logAction($report, $user, ApprovalAction::APPROVE, $comments);

            $this->processNextStep($report, $currentApproval->approval_step);
        });
    }

    private function processNextStep(Report $report, WorkflowStep $completedStep): void
    {
        $workflowSequence = [
            WorkflowStep::RISK_ANALYST,
            WorkflowStep::KADEPT_BISNIS,
            WorkflowStep::KADEPT_RISK,
        ];
        
        $currentIndex = array_search($completedStep, $workflowSequence);
        
        if ($currentIndex === false) {
            throw new Exception("Invalid workflow step: {$completedStep->name}");
        }
        
        if ($currentIndex === count($workflowSequence) - 1) {
            $this->completeWorkflow($report);
        } else {
            $nextStep = $workflowSequence[$currentIndex + 1];
            $this->createNextApprovalStep($report, $nextStep);
            
            $report->update(['status' => ReportStatus::SUBMITTED]);
            
            $this->notifyCurrentApprover($report);
        }
    }

    private function createNextApprovalStep(Report $report, WorkflowStep $step): void
    {
        $approver = $this->getDefaultAssigned($step);

        ReportApproval::create([
            'report_id' => $report->id,
            'approval_step' => $step,
            'status' => ApprovalStatus::PENDING,
            'assigned_to' => null,
            'assigned_at' => null,
        ]);
    }

    private function completeWorkflow(Report $report): void
    {
        $report->update(['status' => ReportStatus::APPROVED]);

        if ($report->creator) {
            $report->creator->notify(new ReportSubmittedNotification($report));
        }

        $this->clearReportCache($report);
    }

    public function rejectstep(Report $report, User $user, string $comments): void
    {
        DB::transaction(function () use ($report, $user, $comments) {
            $currentApproval = $this->getCurrentApproval($report);

            $this->validateApprovalPermission($currentApproval, $user);

            $currentApproval->update(['status' => ApprovalStatus::REJECTED]);

            $report->update([
                'status' => ReportStatus::REJECTED,
                'rejection_reason' => $comments,
            ]);

            $report->approvals()
                ->where('status', ApprovalStatus::PENDING)
                ->where('id', '!=', $currentApproval->id)
                ->delete();

            $this->logAction($report, $user, ApprovalAction::REJECT, $currentApproval->approval_step, $comments);

            if ($report->creator) {
                $report->creator->notify(new ReportRejectedNotification($report, $comments));
            }

            $this->clearReportCache($report);

        });
    }

    public function canViewReport(Report $report, User $user): bool
    {
        if ($report->created_by === $user->id) {
            return true;
        }

        $userRoles = $this->getUserRoles($user);

        if (in_array('super_admin', $userRoles)) {
            return true;
        }

        if (in_array($report->status, [ReportStatus::SUBMITTED, ReportStatus::APPROVED])) {
            return !empty(array_intersect($userRoles, ['risk_analyst', 'kadept_bisnis', 'kadept_risk']));
        }

        return false;
    }

    public function canApproveCurrentStep(Report $report, User $user): bool
    {
        $currentApproval = $this->getCurrentApproval($report);
        
        return $currentApproval && 
               $currentApproval->assigned_to === $user->id && 
               $currentApproval->status === ApprovalStatus::PENDING;
    }

    private function getCurrentApproval(Report $report): ReportApproval
    {
        return $report->approvals()
            ->select(['id', 'approval_step', 'status', 'assigned_to', 'assigned_at'])
            ->where('status', ApprovalStatus::PENDING)
            ->orderBy('approval_step')
            ->first();
    }

    private function getUserRoles(User $user): array
    {
        $cacheKey = "user_roles_{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            return $user->roles()->pluck('name')->toArray();
        });
    }

    private function getDefaultAssigned(WorkflowStep $step): User
    {
        $cacheKey = "workflow_assigned_{$step->value}";

        return Cache::remember($cacheKey, 3600, function () use ($step) {
            $roleMapping = [
                WorkflowStep::RISK_ANALYST->value => 'risk_analyst',
                WorkflowStep::KADEPT_BISNIS->value => 'kadept_bisnis',
                WorkflowStep::KADEPT_RISK->value => 'kadept_risk',
            ];

            $roleName = $roleMapping[$step->value] ?? null;

            if (!$roleName) {
                throw new Exception("No role mapping found for workflow step: {$step->name}");
            }

            $user = User::whereHas('roles', function ($query) use ($roleName) {
                        $query->where('name', $roleName);
                    })->first();

            if (!$user) {
                $user = User::whereHas('roles', function($query) {
                    $query->where('name', 'super_admin');
                })->first();
            }
        
            if (!$user) {
                throw new Exception("No user found with role '{$roleName}' or 'super_admin'");
            }

            return $user;
        });
    }

    public function getApprovalProgress(Report $report): array
    {
        $cacheKey = "approval_progress_{$report->id}_{$report->updated_at->timestamp}";

        return Cache::remember($cacheKey, 300, function () use ($report) {
            $approvals = $report->approvals()
                ->with('assigned:id,name')
                ->select(['id', 'approval_step', 'status', 'assigned_to', 'assigned_at'])
                ->get();
            return $approvals->map(function ($approval) {
                return [
                    'step' => $approval->approval_step,
                    'step_name' => WorkflowStep::labels()[$approval->approval_step->value],
                    'status' => $approval->status,
                    'assigned_to' => $approval->assigned,
                    'assigned_at' => $approval->assigned_at,
                    'is_current' => $approval->status === ApprovalStatus::PENDING,
                ];
            })->toArray();
        });
    }

    private function clearReportCache(Report $report): void
    {
        Cache::forget("approval_progress_{$report->id}_{$report->updated_at->timestamp}");
        Cache::forget("approval_history_{$report->id}");
    }

    private function validateApprovalPermission(ReportApproval $approval, User $user): void
    {
        if (!$approval) {
            throw new Exception('Tidak ada langkah approval yang aktif.');
        }

        if ($approval->assigned_to !== $user->id) {
            throw new Exception('Anda tidak memiliki akses untuk menyetujui langkah ini.');
        }

        if ($approval->status !== ApprovalStatus::PENDING) {
            throw new Exception('Langkah approval ini sudah diproses.');
        }
    }

    private function notifyCurrentApprover(Report $report): void
    {
        $currentApproval = $this->getCurrentApproval($report);
        
        if ($currentApproval && $currentApproval->assigned) {
            $currentApproval->assigned->notify(new ReportSubmittedNotification($report));
        }
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

    public function getApprovalHistory(Report $report): array
    {
        $cacheKey = "approval_history_{$report->id}";
        
        return Cache::remember($cacheKey, 600, function () use ($report) {
            return $report->approvalHistory()
                ->with('user:id,name')
                ->select(['id', 'user_id', 'action', 'approval_step', 'comments', 'action_at', 'metadata'])
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
        });
    }
}