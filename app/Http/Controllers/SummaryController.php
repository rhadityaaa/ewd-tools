<?php

namespace App\Http\Controllers;

use App\Enum\ReportStatus;
use App\Models\Report;
use App\Models\ReportSummary;
use App\Services\ApprovalWorkflowService;
use App\Services\SummaryCalculationService;
use App\Services\SummaryService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SummaryController extends Controller
{
    protected SummaryService $summaryService;
    protected ApprovalWorkflowService $approvalWorkflowService;

    public function __construct(
        SummaryService $summaryService,
        ApprovalWorkflowService $approvalWorkflowService
    ) {
        $this->summaryService = $summaryService;
        $this->approvalWorkflowService = $approvalWorkflowService;
    }

    public function show(Request $request)
    {
        // Fix: Get reportId from query parameter instead of input
        $reportId = $request->query('reportId') ?? $request->input('reportId');
        
        if (!$reportId) {
            return redirect()->route('dashboard')->with('error', 'Report ID tidak ditemukan');
        }

        try {
            $summaryData = $this->summaryService->getSummaryData($reportId);

            return Inertia::render('Summary', $summaryData);
        } catch (Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Gagal memuat data: ' . $e->getMessage());
        }
    }

    public function update(Request $request, int $reportId)
    {
        try {
            $validated = $request->validate([
                'businessNotes' => 'nullable|string',
                'reviewerNotes' => 'nullable|string',
                'override' => 'boolean',
                'collectibilityIndicator' => 'integer|min:1|max:5',
                'overrideReason' => 'nullable|string|required_if:override,true',
                'finalClassification' => 'required|string|in:SAFE,WATCHLIST',
            ]);
            
            $report = Report::findOrFail($reportId);
            
            // Simpan summary data
            ReportSummary::updateOrCreate(
                ['report_id' => $reportId],
                [
                    'business_notes' => $validated['businessNotes'] ?? '',
                    'reviewer_notes' => $validated['reviewerNotes'] ?? '',
                    'is_override' => $validated['override'] ?? false,
                    'indicative_collectibility' => $validated['collectibilityIndicator'] ?? 1,
                    'override_reason' => $validated['overrideReason'] ?? '',
                    'final_classification' => strtolower($validated['finalClassification']),
                ]
            );
            
            // Trigger approval workflow jika laporan masih draft
            // Fix: $report->status sudah berupa enum, tidak perlu di-convert lagi
            $reportStatus = $report->status ?? ReportStatus::DRAFT;
            if ($reportStatus->canBeSubmitted()) {
                $this->approvalWorkflowService->submitReport($report, Auth::user());
                
                return response()->json([
                    'success' => true,
                    'message' => 'Summary berhasil disimpan dan laporan telah dikirim untuk approval',
                    'status' => 'submitted_for_approval'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Summary berhasil disimpan',
                'status' => 'saved'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getPreviousPeriodClassification(Report $report)
    {
        $previousReport = Report::where('borrower_id', $report->borrower_id)
            ->where('period_id', '<', $report->period_id)
            ->with('summary')
            ->orderBy('period_id', 'desc')
            ->first();
            
        return $previousReport?->summary?->final_classification;
    }
}
