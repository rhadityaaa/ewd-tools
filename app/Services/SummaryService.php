<?php

namespace App\Services;

use App\Models\Report;

class SummaryService
{
    protected SummaryCalculationService $calculationService;

    public function __construct(SummaryCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    public function getSummaryData(int $reportId)
    {
        $report = Report::with([
            'borrower.details',
            'borrower.facilities',
            'borrower.division',
            'template',
            'period',
            'summary',
            'creator'
        ])->findOrFail($reportId);

        $summaryData = $this->calculationService->calculateAndStoreSummary($reportId);

        $previosPeriodClassification = $this->getPreviousPeriodClassification($report);

        return [
            'reportId' => $reportId,
            'reportData' => $report,
            'summaryCalculation' => $summaryData,
            'previousPeriodClasification' => $previosPeriodClassification,
        ];
    }

    private function getPreviousPeriodClassification(Report $report)
    {
        $previousReport = Report::where('borrower_id',
        $report->borrower_id)
            ->where('period_id', '<', $report->period_id)
            ->with('summary')
            ->orderBy('period_id', 'desc')
            ->first();

        return $previousReport?->summary?->final_classification;
    }
}