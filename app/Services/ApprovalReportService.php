<?php

namespace App\Services;

use App\Enum\ApprovalStatus;
use App\Enum\ReportStatus;
use App\Enum\WorkflowStep;
use App\Models\Period;
use App\Models\Report;
use App\Models\ReportApproval;
use App\Models\ApprovalHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalReportService
{
    // ✅ NEW: Method for getting approval reports with filters
    public function getApprovalReports(string $period = 'month', ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Report::with([
            'borrower:id,name,division_id',
            'borrower.division:id,name,code',
            'period:id,name,start_date,end_date',
            'template:id,name',
            'creator:id,name,email',
            'summary',
            'approvals.assigned:id,name',
            'approvalHistory.user:id,name'
        ]);

        // Apply date filters
        if ($startDate && $endDate) {
            $query->whereBetween('submitted_at', [$startDate, $endDate]);
        } else {
            // Default period filtering
            switch ($period) {
                case 'week':
                    $query->where('submitted_at', '>=', Carbon::now()->startOfWeek());
                    break;
                case 'month':
                    $query->where('submitted_at', '>=', Carbon::now()->startOfMonth());
                    break;
                case 'quarter':
                    $query->where('submitted_at', '>=', Carbon::now()->startOfQuarter());
                    break;
                case 'year':
                    $query->where('submitted_at', '>=', Carbon::now()->startOfYear());
                    break;
            }
        }

        $reports = $query->orderBy('submitted_at', 'desc')->get();

        // Calculate statistics
        $statistics = $this->calculateApprovalStatistics($reports);
        
        // Group reports by status
        $reportsByStatus = $this->groupReportsByStatus($reports);
        
        // Get workflow performance
        $workflowPerformance = $this->calculateWorkflowPerformance($reports);
        
        // Get approval timeline
        $approvalTimeline = $this->getApprovalTimeline($reports);
        
        return [
            'statistics' => $statistics,
            'reportsByStatus' => $reportsByStatus,
            'workflowPerformance' => $workflowPerformance,
            'approvalTimeline' => $approvalTimeline,
            'reports' => $reports,
            'period' => $period,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }

    public function getApprovalReportByPeriod(int $periodId): array
    {
        $period = Period::findOrFail($periodId);
        
        // Get all reports for the period
        $reports = Report::with([
            'borrower:id,name,division_id',
            'borrower.division:id,name,code',
            'approvals.assigned:id,name',
            'approvalHistory.user:id,name'
        ])
        ->where('period_id', $periodId)
        ->get();

        // Calculate statistics
        $statistics = $this->calculateApprovalStatistics($reports);
        
        // Group reports by status
        $reportsByStatus = $this->groupReportsByStatus($reports);
        
        // Get workflow performance
        $workflowPerformance = $this->calculateWorkflowPerformance($reports);
        
        // Get approval timeline
        $approvalTimeline = $this->getApprovalTimeline($reports);
        
        return [
            'period' => $period,
            'statistics' => $statistics,
            'reportsByStatus' => $reportsByStatus,
            'workflowPerformance' => $workflowPerformance,
            'approvalTimeline' => $approvalTimeline,
            'reports' => $reports,
        ];
    }

    public function getApprovalSummary(): array
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Current month statistics
        $currentStats = $this->getMonthlyStatistics($currentMonth);
        
        // Last month statistics for comparison
        $lastStats = $this->getMonthlyStatistics($lastMonth);
        
        // Trend analysis
        $trends = $this->calculateTrends($currentStats, $lastStats);
        
        // Top performers
        $topPerformers = $this->getTopPerformers();
        
        // Bottlenecks
        $bottlenecks = $this->identifyBottlenecks();
        
        return [
            'currentStats' => $currentStats,
            'lastStats' => $lastStats,
            'trends' => $trends,
            'topPerformers' => $topPerformers,
            'bottlenecks' => $bottlenecks,
        ];
    }

    public function getWorkflowDetails(int $reportId): array
    {
        $report = Report::with([
            'borrower:id,name',
            'approvals.assigned:id,name',
            'approvalHistory.user:id,name'
        ])->findOrFail($reportId);

        $workflowSteps = $this->buildWorkflowSteps($report);
        $timeline = $this->buildTimeline($report);
        $metrics = $this->calculateReportMetrics($report);
        
        return [
            'report' => $report,
            'workflowSteps' => $workflowSteps,
            'timeline' => $timeline,
            'metrics' => $metrics,
        ];
    }

    // ✅ NEW: Export reports method
    public function exportReports(string $period = 'month', ?string $startDate = null, ?string $endDate = null)
    {
        $data = $this->getApprovalReports($period, $startDate, $endDate);
        
        // For now, return JSON response. Later can be implemented with Laravel Excel
        return response()->json([
            'message' => 'Export functionality',
            'period' => $period,
            'total_reports' => $data['statistics']['total'],
            'approval_rate' => $data['statistics']['approval_rate'] . '%',
            'data' => $data
        ]);
    }

    private function calculateApprovalStatistics(Collection $reports): array
    {
        $total = $reports->count();
        $approved = $reports->where('status', ReportStatus::APPROVED)->count();
        $rejected = $reports->where('status', ReportStatus::REJECTED)->count();
        $pending = $reports->where('status', ReportStatus::UNDER_REVIEW)->count();
        $submitted = $reports->where('status', ReportStatus::SUBMITTED)->count();
        
        return [
            'total' => $total,
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'submitted' => $submitted,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'rejection_rate' => $total > 0 ? round(($rejected / $total) * 100, 2) : 0,
        ];
    }

    private function groupReportsByStatus(Collection $reports): array
    {
        return [
            'approved' => $reports->where('status', ReportStatus::APPROVED)->values(),
            'rejected' => $reports->where('status', ReportStatus::REJECTED)->values(),
            'pending' => $reports->where('status', ReportStatus::UNDER_REVIEW)->values(),
            'submitted' => $reports->where('status', ReportStatus::SUBMITTED)->values(),
        ];
    }

    // ✅ FIX: Only include actual approval steps (exclude Unit Bisnis)
    private function calculateWorkflowPerformance(Collection $reports): array
    {
        $stepPerformance = [];
        
        // ✅ Only calculate for actual approval steps
        $approvalSteps = [
            WorkflowStep::RISK_ANALYST,
            WorkflowStep::KADEPT_BISNIS,
            WorkflowStep::KADEPT_RISK,
        ];
        
        foreach ($approvalSteps as $step) {
            $approvals = ReportApproval::whereIn('report_id', $reports->pluck('id'))
                ->where('approval_step', $step)
                ->get();
            
            $avgTime = $this->calculateAverageApprovalTime($approvals);
            $completionRate = $this->calculateCompletionRate($approvals);
            
            $stepPerformance[$step->value] = [
                'step' => $step,
                'step_name' => $this->getStepLabel($step),
                'total_approvals' => $approvals->count(),
                'completed' => $approvals->where('status', ApprovalStatus::APPROVED)->count(),
                'rejected' => $approvals->where('status', ApprovalStatus::REJECTED)->count(),
                'pending' => $approvals->where('status', ApprovalStatus::PENDING)->count(),
                'avg_time_hours' => $avgTime,
                'completion_rate' => $completionRate,
            ];
        }
        
        return $stepPerformance;
    }

    // ✅ NEW: Get proper step labels
    private function getStepLabel(WorkflowStep $step): string
    {
        return match($step) {
            WorkflowStep::RISK_ANALYST => 'Risk Analyst',
            WorkflowStep::KADEPT_BISNIS => 'Kepala Departemen Bisnis',
            WorkflowStep::KADEPT_RISK => 'Kepala Departemen Risk',
            default => $step->name,
        };
    }

    private function getApprovalTimeline(Collection $reports): array
    {
        $timeline = [];
        
        foreach ($reports as $report) {
            $history = $report->approvalHistory()->orderBy('action_at')->get();
            
            foreach ($history as $entry) {
                $date = $entry->action_at->format('Y-m-d');
                
                if (!isset($timeline[$date])) {
                    $timeline[$date] = [
                        'date' => $date,
                        'submitted' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                    ];
                }
                
                // ✅ FIX: Use proper enum comparison
                switch ($entry->action) {
                    case \App\Enum\ApprovalAction::SUBMIT:
                        $timeline[$date]['submitted']++;
                        break;
                    case \App\Enum\ApprovalAction::APPROVE:
                        $timeline[$date]['approved']++;
                        break;
                    case \App\Enum\ApprovalAction::REJECT:
                        $timeline[$date]['rejected']++;
                        break;
                }
            }
        }
        
        return array_values($timeline);
    }

    private function getMonthlyStatistics(Carbon $month): array
    {
        $reports = Report::whereBetween('created_at', [
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth()
        ])->get();
        
        return $this->calculateApprovalStatistics($reports);
    }

    private function calculateTrends(array $current, array $last): array
    {
        $trends = [];
        
        foreach (['total', 'approved', 'rejected', 'approval_rate'] as $metric) {
            $currentValue = $current[$metric] ?? 0;
            $lastValue = $last[$metric] ?? 0;
            
            if ($lastValue > 0) {
                $change = (($currentValue - $lastValue) / $lastValue) * 100;
            } else {
                $change = $currentValue > 0 ? 100 : 0;
            }
            
            $trends[$metric] = [
                'current' => $currentValue,
                'last' => $lastValue,
                'change' => round($change, 2),
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            ];
        }
        
        return $trends;
    }

    private function getTopPerformers(): array
    {
        return DB::table('approval_histories')
            ->join('users', 'approval_histories.user_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                DB::raw('COUNT(*) as total_approvals'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, approval_histories.created_at, approval_histories.action_at)) as avg_time')
            )
            ->where('approval_histories.action', \App\Enum\ApprovalAction::APPROVE)
            ->where('approval_histories.created_at', '>=', Carbon::now()->subMonth())
            ->groupBy('users.id', 'users.name')
            ->orderBy('total_approvals', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    // ✅ FIX: Only check actual approval steps for bottlenecks
    private function identifyBottlenecks(): array
    {
        $bottlenecks = [];
        
        $approvalSteps = [
            WorkflowStep::RISK_ANALYST,
            WorkflowStep::KADEPT_BISNIS,
            WorkflowStep::KADEPT_RISK,
        ];
        
        foreach ($approvalSteps as $step) {
            $pendingApprovals = ReportApproval::where('approval_step', $step)
                ->where('status', ApprovalStatus::PENDING)
                ->where('assigned_at', '<', Carbon::now()->subDays(3))
                ->count();
            
            if ($pendingApprovals > 0) {
                $bottlenecks[] = [
                    'step' => $step,
                    'step_name' => $this->getStepLabel($step),
                    'pending_count' => $pendingApprovals,
                    'severity' => $this->calculateSeverity($pendingApprovals),
                ];
            }
        }
        
        return $bottlenecks;
    }

    // ✅ FIX: Only build workflow steps for actual approval steps
    private function buildWorkflowSteps(Report $report): array
    {
        $steps = [];
        
        $approvalSteps = [
            WorkflowStep::RISK_ANALYST,
            WorkflowStep::KADEPT_BISNIS,
            WorkflowStep::KADEPT_RISK,
        ];
        
        foreach ($approvalSteps as $index => $step) {
            $approval = $report->approvals()->where('approval_step', $step)->first();
            
            $steps[] = [
                'step' => $step,
                'step_number' => $index + 1, // ✅ Correct numbering: 1, 2, 3
                'step_name' => $this->getStepLabel($step),
                'status' => $approval?->status ?? ApprovalStatus::PENDING,
                'assigned_to' => $approval?->assigned,
                'assigned_at' => $approval?->assigned_at,
                'completed_at' => $approval?->updated_at,
                'is_current' => $approval?->status === ApprovalStatus::PENDING,
            ];
        }
        
        return $steps;
    }

    private function buildTimeline(Report $report): array
    {
        return $report->approvalHistory()
            ->with('user:id,name')
            ->orderBy('action_at')
            ->get()
            ->map(function ($entry) {
                return [
                    'action' => $entry->action,
                    'user' => $entry->user,
                    'step' => $entry->approval_step,
                    'comments' => $entry->comments,
                    'action_at' => $entry->action_at,
                ];
            })
            ->toArray();
    }

    private function calculateReportMetrics(Report $report): array
    {
        $submitted = $report->submitted_at;
        $completed = $report->status === ReportStatus::APPROVED ? $report->updated_at : null;
        
        $totalTime = $submitted && $completed 
            ? $submitted->diffInHours($completed)
            : null;
        
        $stepTimes = [];
        foreach ($report->approvals as $approval) {
            if ($approval->status !== ApprovalStatus::PENDING) {
                $stepTimes[] = [
                    'step' => $approval->approval_step,
                    'time_hours' => $approval->assigned_at->diffInHours($approval->updated_at),
                ];
            }
        }
        
        return [
            'total_time_hours' => $totalTime,
            'step_times' => $stepTimes,
            'current_step' => $report->getCurrentStep(),
            'progress_percentage' => $this->calculateProgress($report),
        ];
    }

    private function calculateAverageApprovalTime(Collection $approvals): float
    {
        $completedApprovals = $approvals->where('status', '!=', ApprovalStatus::PENDING);
        
        if ($completedApprovals->isEmpty()) {
            return 0;
        }
        
        $totalHours = $completedApprovals->sum(function ($approval) {
            return $approval->assigned_at->diffInHours($approval->updated_at);
        });
        
        return round($totalHours / $completedApprovals->count(), 2);
    }

    private function calculateCompletionRate(Collection $approvals): float
    {
        if ($approvals->isEmpty()) {
            return 0;
        }
        
        $completed = $approvals->where('status', '!=', ApprovalStatus::PENDING)->count();
        return round(($completed / $approvals->count()) * 100, 2);
    }

    private function calculateSeverity(int $pendingCount): string
    {
        if ($pendingCount >= 10) {
            return 'high';
        } elseif ($pendingCount >= 5) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    // ✅ FIX: Only count actual approval steps
    private function calculateProgress(Report $report): float
    {
        $approvalSteps = [
            WorkflowStep::RISK_ANALYST,
            WorkflowStep::KADEPT_BISNIS,
            WorkflowStep::KADEPT_RISK,
        ];
        
        $totalSteps = count($approvalSteps);
        $completedSteps = $report->approvals()
            ->whereIn('approval_step', $approvalSteps)
            ->where('status', ApprovalStatus::APPROVED)
            ->count();
        
        return round(($completedSteps / $totalSteps) * 100, 2);
    }

    // ✅ KEEP: Export method for backward compatibility
    public function exportApprovalReport(Period $period, array $data)
    {
        return response()->json([
            'message' => 'Export functionality to be implemented',
            'period' => $period->name,
            'data_summary' => [
                'total_reports' => $data['statistics']['total'],
                'approval_rate' => $data['statistics']['approval_rate'] . '%'
            ]
        ]);
    }
}