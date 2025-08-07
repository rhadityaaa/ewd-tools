<?php

namespace App\Services;

use App\Models\Report;

class ReportService
{
    public function getAllReport()
    {
        $reports = Report::query()
            ->with(['borrower', 'template', 'period', 'summary'])
            ->latest()
            ->paginate(15);

        return $reports;
    }

    public function getReportById(int $id)
    {
        $report = Report::with(['borrower', 'template', 'period', 'summary', 'creator'])->findOrFail($id);

        return $report;
    }

    public function deleteReport(Report $report)
    {
        $report->delete();
    }
}