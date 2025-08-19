<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\ApprovalWorkflowService;
use App\Enum\ApprovalStatus;
use App\Enum\ReportStatus;
use App\Models\ReportApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function __construct(
        protected ApprovalWorkflowService $approvalWorkflowService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Debug information
        $debugData = [
            'current_user_id' => $user->id,
            'current_user_roles' => $user->roles->pluck('name'),
            'total_reports' => Report::count(),
            'submitted_reports' => Report::whereIn('status', [ReportStatus::SUBMITTED, ReportStatus::UNDER_REVIEW])->count(),
            'total_approvals' => ReportApproval::count(),
            'pending_approvals' => ReportApproval::where('status', ApprovalStatus::PENDING)->count(),
            'user_pending_approvals' => ReportApproval::where('assigned_to', $user->id)
                ->where('status', ApprovalStatus::PENDING)->count(),
        ];
        
        Log::info('Approval Debug', $debugData);
        
        // Check if user has any approval role
        if (!$user->hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk halaman approval.');
        }
        
        // Get reports pending approval for current user
        $pendingReports = Report::with([
            'borrower:id,name',
            'borrower.division:id,name,code',
            'approvals' => function($query) use ($user) {
                $query->where('assigned_to', $user->id)
                      ->where('status', ApprovalStatus::PENDING);
            },
            'approvals.assigned:id,name'
        ])
        ->whereHas('approvals', function($query) use ($user) {
            $query->where('assigned_to', $user->id);
                //   ->where('status', ApprovalStatus::PENDING);
        })
        ->whereIn('status', [ReportStatus::SUBMITTED, ReportStatus::UNDER_REVIEW])
        ->orderBy('submitted_at', 'asc')
        ->get();

        // Debug information (remove in production)
        $debugInfo = [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name'),
            'total_reports' => Report::count(),
            'total_approvals' => \App\Models\ReportApproval::count(),
            'user_approvals' => \App\Models\ReportApproval::where('assigned_to', $user->id)->count(),
            'pending_count' => $pendingReports->count(),
        ];

        return Inertia::render('approval/Index', [
            'pendingReports' => $pendingReports,
            'userRole' => $user->roles->pluck('name')->first(),
            'debugInfo' => $debugInfo, // Remove this in production
        ]);
    }

    public function show(Report $report)
    {
        $user = Auth::user();
        
        // Check if user has any approval role
        if (!$user->hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            abort(403, 'Anda tidak memiliki akses untuk melihat detail approval.');
        }
        
        $report->load([
            'borrower:id,name',
            'borrower.division:id,name,code',
            'approvals.assigned:id,name',
            'approvalHistory.user:id,name'
        ]);

        $workflowSteps = $this->approvalWorkflowService->getApprovalProgress($report);
        $approvalHistory = $this->approvalWorkflowService->getApprovalHistory($report);
        
        // ✅ FIX: Properly check if current user can approve current step
        $currentApproval = $report->approvals()
            ->where('status', ApprovalStatus::PENDING)
            ->where('assigned_to', $user->id)
            ->first();
        
        $canApprove = $currentApproval !== null;
        $canOverride = $user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin']);
        
        // ✅ DEBUG: Log the permission check
        Log::info('Approval Show Permission Check', [
            'user_id' => $user->id,
            'user_roles' => $user->roles->pluck('name'),
            'report_id' => $report->id,
            'current_approval' => $currentApproval ? [
                'id' => $currentApproval->id,
                'step' => $currentApproval->approval_step,
                'assigned_to' => $currentApproval->assigned_to,
            ] : null,
            'canApprove' => $canApprove,
            'canOverride' => $canOverride,
        ]);
        
        return Inertia::render('approval/Show', [
            'report' => $report,
            'workflowSteps' => $workflowSteps,
            'approvalHistory' => $approvalHistory,
            'canApprove' => $canApprove,  // ✅ Send correct permission
            'canOverride' => $canOverride, // ✅ Send correct permission
            'userRole' => $user->roles->pluck('name')->first(),
        ]);
    }

    public function approve(Request $request, Report $report)
    {
        $user = Auth::user();
        
        // Additional role validation
        if (!$user->hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menyetujui laporan.');
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'comments' => 'nullable|string|max:1000',
        ], [
            'comments.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->approvalWorkflowService->approveStep(
                $report,
                $user,
                $request->comments
            );

            return redirect()->back()->with('success', 'Laporan berhasil disetujui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, Report $report)
    {
        $user = Auth::user();
        
        // Additional role validation
        if (!$user->hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menolak laporan.');
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000',
        ], [
            'comments.required' => 'Komentar wajib diisi saat menolak laporan.',
            'comments.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->approvalWorkflowService->rejectStep(
                $report,
                $user,
                $request->comments
            );

            return redirect()->back()->with('success', 'Laporan berhasil ditolak.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function requestRevision(Request $request, Report $report)
    {
        $user = Auth::user();
        
        // Additional role validation
        if (!$user->hasAnyRole(['risk_analyst', 'kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk meminta revisi.');
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000',
        ], [
            'comments.required' => 'Komentar wajib diisi saat meminta revisi.',
            'comments.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->approvalWorkflowService->requestRevision(
                $report,
                $user,
                $request->comments
            );

            return redirect()->back()->with('success', 'Permintaan revisi berhasil dikirim.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function override(Request $request, Report $report)
    {
        $user = Auth::user();
        
        // Additional role validation for override
        if (!$user->hasAnyRole(['kadept_bisnis', 'kadept_risk', 'super_admin'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk override approval.');
        }
        
        // Validate request
        $validator = Validator::make($request->all(), [
            'comments' => 'required|string|max:1000',
        ], [
            'comments.required' => 'Komentar wajib diisi saat override approval.',
            'comments.max' => 'Komentar tidak boleh lebih dari 1000 karakter.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $this->approvalWorkflowService->overrideApproval(
                $report,
                $user,
                $request->comments
            );

            return redirect()->back()->with('success', 'Laporan berhasil di-override.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}