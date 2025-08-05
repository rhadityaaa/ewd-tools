<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportSummary;
use App\Services\SummaryCalculationService;
use App\Services\SummaryService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SummaryController extends Controller
{
    protected SummaryService $summaryService;

    public function __construct(SummaryService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function show(Request $request)
    {
        $reportId = $request->input('reportId');
        
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

    private function getPreviousPeriodClassification(Report $report)
    {
        $previousReport = Report::where('borrower_id', $report->borrower_id)
            ->where('period_id', '<', $report->period_id)
            ->with('summary')
            ->orderBy('period_id', 'desc')
            ->first();
            
        return $previousReport?->summary?->final_classification;
    }

    public function update(Request $request, int $reportId)
    {
        try {
            $validated = $request->validate([
                'businessNotes' => 'nullable|string',
                'reviewerNotes' => 'nullable|string',
                'override' => 'boolean',
                'collectibilityIndicator' => 'integer|min:0|max:4',
                'overrideReason' => 'nullable|string|required_if:override,true',
            ]);
            
            ReportSummary::updateOrCreate(
                ['report_id' => $reportId],
                [
                    'business_notes' => $validated['businessNotes'] ?? '',
                    'reviewer_notes' => $validated['reviewerNotes'] ?? '',
                    'is_override' => $validated['override'] ?? false,
                    'indicative_collectibility' => $validated['collectibilityIndicator'] ?? 0,
                    'override_reason' => $validated['overrideReason'] ?? '',
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}