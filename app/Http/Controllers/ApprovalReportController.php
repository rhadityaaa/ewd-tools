<?php

namespace App\Http\Controllers;

use App\Services\ApprovalReportService;
use App\Models\Period;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalReportController extends Controller
{
    public function __construct(
        protected ApprovalReportService $approvalReportService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        
        // ✅ Check access
        if (!$user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk melihat laporan persetujuan.');
        }

        // ✅ Get all periods for dropdown
        $periods = Period::orderBy('start_date', 'desc')->get();
        
        // ✅ Get selected period from request
        $selectedPeriodId = $request->input('period_id');
        
        // ✅ Get approval data - if no period selected, get recent data
        $approvalData = null;
        if ($selectedPeriodId) {
            $approvalData = $this->approvalReportService->getApprovalReportByPeriod($selectedPeriodId);
        } else {
            // ✅ Default: get data for current month
            $approvalData = $this->approvalReportService->getApprovalReports('month');
        }
        
        return Inertia::render('approval-report/Index', [
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriodId,
            'approvalData' => $approvalData,
        ]);
    }

    // ✅ NEW: Show method for detailed report view
    public function show(Report $report)
    {
        $user = auth()->user();
        
        // ✅ Give super_admin full access
        if (!$user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk melihat detail laporan.');
        }

        // Load all related data
        $report->load([
            'borrower:id,name,division_id',
            'borrower.division:id,name,code',
            'borrower.details',
            'borrower.facilities',
            'period:id,name,start_date,end_date',
            'template:id,name',
            'creator:id,name,email',
            'summary',
            'aspects.aspectVersion.aspect',
            'aspects.aspectVersion.questionVersions.questionOptions',
            'answers.questionVersion.question',
            'answers.questionOption',
            'approvals.assigned:id,name',
            'approvalHistory.user:id,name'
        ]);

        // Get workflow progress and history
        $workflowSteps = $this->approvalReportService->getWorkflowDetails($report->id);
        
        // Calculate report metrics
        $metrics = $this->calculateReportMetrics($report);
        
        // Get approval timeline
        $timeline = $this->buildApprovalTimeline($report);
        
        return Inertia::render('approval-report/Show', [
            'report' => $report,
            'workflowSteps' => $workflowSteps,
            'metrics' => $metrics,
            'timeline' => $timeline,
        ]);
    }

    public function summary()
    {
        $summaryData = $this->approvalReportService->getApprovalSummary();
        
        return Inertia::render('approval-report/Summary', [
            'summaryData' => $summaryData,
        ]);
    }

    public function workflow(int $reportId)
    {
        $workflowData = $this->approvalReportService->getWorkflowDetails($reportId);
        
        return Inertia::render('approval-report/Workflow', [
            'workflowData' => $workflowData,
        ]);
    }

    // ✅ Update export method for super_admin access
    public function export(Request $request)
    {
        $user = auth()->user();
        
        // ✅ Give super_admin full access
        if (!$user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk export laporan.');
        }

        $periodId = $request->route('period') ?? $request->input('period_id');
        
        if ($periodId) {
            $period = Period::findOrFail($periodId);
            $approvalData = $this->approvalReportService->getApprovalReportByPeriod($periodId);
            return $this->approvalReportService->exportApprovalReport($period, $approvalData);
        }
        
        return response()->json(['error' => 'Period ID required'], 400);
    }

    // ✅ Helper methods for Show page
    private function calculateReportMetrics(Report $report): array
    {
        $submittedAt = $report->submitted_at;
        $completedAt = $report->status === \App\Enum\ReportStatus::APPROVED ? $report->updated_at : null;
        
        $totalProcessingTime = $submittedAt && $completedAt 
            ? $submittedAt->diffInHours($completedAt)
            : null;
        
        $approvalSteps = $report->approvals->map(function ($approval) {
            return [
                'step' => $approval->approval_step,
                'step_name' => \App\Enum\WorkflowStep::labels()[$approval->approval_step->value] ?? 'Unknown',
                'status' => $approval->status,
                'assigned_to' => $approval->assigned->name ?? 'N/A',
                'processing_time' => $approval->assigned_at && $approval->updated_at 
                    ? $approval->assigned_at->diffInHours($approval->updated_at)
                    : null,
            ];
        });
        
        return [
            'total_processing_time_hours' => $totalProcessingTime,
            'approval_steps' => $approvalSteps,
            'current_step' => $report->getCurrentStep(),
            'progress_percentage' => $this->calculateProgressPercentage($report),
        ];
    }

    private function buildApprovalTimeline(Report $report): array
    {
        return $report->approvalHistory
            ->sortBy('action_at')
            ->map(function ($history) {
                return [
                    'action' => $history->action,
                    'action_label' => $this->getActionLabel($history->action),
                    'user' => $history->user->name,
                    'step' => $history->approval_step,
                    'step_name' => $history->approval_step 
                        ? \App\Enum\WorkflowStep::labels()[$history->approval_step->value] ?? 'N/A'
                        : 'N/A',
                    'comments' => $history->comments,
                    'action_at' => $history->action_at,
                    'metadata' => $history->metadata,
                ];
            })
            ->values()
            ->toArray();
    }

    private function getActionLabel($action): string
    {
        return match($action) {
            \App\Enum\ApprovalAction::SUBMIT => 'Submit Laporan',
            \App\Enum\ApprovalAction::APPROVE => 'Menyetujui',
            \App\Enum\ApprovalAction::REJECT => 'Menolak',
            \App\Enum\ApprovalAction::REVISION => 'Minta Revisi',
            \App\Enum\ApprovalAction::OVERRIDE => 'Override',
            \App\Enum\ApprovalAction::REASSIGN => 'Reassign',
            default => 'Unknown Action',
        };
    }

    private function calculateProgressPercentage(Report $report): float
    {
        $totalSteps = $report->approvals->count();
        $completedSteps = $report->approvals->where('status', \App\Enum\ApprovalStatus::APPROVED)->count();
        
        return $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100, 2) : 0;
    }
}