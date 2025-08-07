<?php

namespace App\Services;

use App\Enum\Classification;
use App\Http\Resources\ReportResource;
use App\Models\MonitoringNote;
use App\Models\Report;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Auth;

class MonitoringNoteService
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function getMonitoringNoteData(int $reportId)
    {
        $report = $this->reportService->getReportById($reportId);
        $watchlist = $this->getWatchlistByReportId($reportId);
        $monitoringNote = $this->getMonitoringNoteByWatchlist($watchlist->id);

        $actionItems = [
            'previous_period' =>
            $monitoringNote->actionItems->where('item_type', 'previous_period')->values(),
            'current_progress' => 
            $monitoringNote->actionItems->where('item_type', 'current_progress')->values(),
            'next_period' => 
            $monitoringNote->actionItems->where('item_type', 'next_period')->values(),
        ];

        return [
            'watchlist' => $watchlist,
            'report' => $report,
            'monitoringNote' => $monitoringNote,
            'actionItems' => $actionItems,
        ];
    }

    public function getWatchlistByReportId(int $reportId)
    {
        return Watchlist::with([
            'borrower',
            'report',
            'addedBy',
            'resolvedBy',
        ])->where('report_id', $reportId)->firstOrFail();
    }

    public function getMonitoringNoteByWatchlist(int $watchlist_id)
    {
        return MonitoringNote::with('actionItems')->where('watchlist_id', $watchlist_id)->firstOrFail();
    }
}