<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportSummary;
use App\Services\SummaryCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SummaryController extends Controller
{
    protected SummaryCalculationService $summaryService;

    public function __construct(SummaryCalculationService $summaryService)
    {
        $this->summaryService = $summaryService;
    }

    public function show(Request $request)
    {
        $reportId = $request->input('reportId');
        
        if (!$reportId) {
            return redirect()->route('dashboard')->with('error', 'Report ID tidak ditemukan');
        }

        // Ambil data report dengan relasi
        $report = Report::with([
            'borrower',
            'template',
            'period',
            'summary'
        ])->findOrFail($reportId);

        // Hitung summary jika belum ada atau perlu diperbarui
        $summaryData = $this->summaryService->calculateAndStoreSummary($reportId);

        return Inertia::render('Summary', [
            'reportData' => $report,
            'reportId' => $reportId,
            'summaryCalculation' => $summaryData
        ]);
    }

    public function update(Request $request, int $reportId)
    {
        try {
            $validated = $request->validate([
                'businessNotes' => 'nullable|string',
                'reviewerNotes' => 'nullable|string',
            ]);
            
            $report = Report::findOrFail($reportId);
            
            ReportSummary::updateOrCreate(
                ['report_id' => $reportId],
                [
                    'business_notes' => $validated['businessNotes'] ?? '',
                    'reviewer_notes' => $validated['reviewerNotes'] ?? ''
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil disimpan'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan catatan: ' . $e->getMessage()
            ], 500);
        }
    }
}