<?php

namespace App\Services;

use App\Enum\Classification;
use App\Http\Resources\ReportResource;
use App\Models\MonitoringNote;
use App\Models\Report;
use App\Models\Watchlist;
use App\Services\ActionItemService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MonitoringNoteService
{
    protected ReportService $reportService;
    protected WatchlistService $watchlistService;
    protected ActionItemService $actionItemService;

    public function __construct(
        ReportService $reportService, 
        WatchlistService $watchlistService,
        ActionItemService $actionItemService
    ) {
        $this->reportService = $reportService;
        $this->watchlistService = $watchlistService;
        $this->actionItemService = $actionItemService;
    }

    public function getMonitoringNoteData(int $reportId)
    {
        $report = $this->reportService->getReportById($reportId);
        $watchlist = $this->getOrCreateWatchlistByReportId($reportId);
        $monitoringNote = $this->getOrCreateMonitoringNoteByWatchlist($watchlist->id, $report->borrower_id);

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
            'isNawRequired' => $this->isNawRequired($reportId),
        ];
    }

    public function getOrCreateWatchlistByReportId(int $reportId)
    {
        // Coba cari watchlist yang sudah ada
        $watchlist = Watchlist::with([
            'borrower',
            'report',
            'addedBy',
            'resolvedBy',
        ])->where('report_id', $reportId)->first();

        // Jika tidak ada, buat baru menggunakan WatchlistService
        if (!$watchlist) {
            $report = Report::findOrFail($reportId);
            $watchlist = $this->watchlistService->getOrCreateWatchlist($report);
            
            // Load relationships setelah dibuat
            $watchlist->load([
                'borrower',
                'report',
                'addedBy',
                'resolvedBy',
            ]);
        }

        return $watchlist;
    }

    public function getOrCreateMonitoringNoteByWatchlist(int $watchlist_id, int $borrowerId = null)
    {
        // Coba cari monitoring note yang sudah ada
        $monitoringNote = MonitoringNote::with('actionItems')
            ->where('watchlist_id', $watchlist_id)
            ->first();

        // Jika tidak ada, buat baru
        if (!$monitoringNote) {
            $monitoringNote = MonitoringNote::create([
                'watchlist_id' => $watchlist_id,
                'watchlist_reason' => '', // Tambahkan default value kosong
                'account_strategy' => '', // Tambahkan default value kosong
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);
            
            // Load relationships setelah dibuat
            $monitoringNote->load('actionItems');
            
            // Auto-copy dari periode sebelumnya jika ada borrowerId
            if ($borrowerId) {
                $this->autoCopyFromPreviousPeriod($monitoringNote->id, $borrowerId);
                // Reload action items setelah copy
                $monitoringNote->load('actionItems');
            }
        } else {
            // Jika monitoring note sudah ada tapi belum ada previous period items, auto-copy
            $hasPreviousItems = $monitoringNote->actionItems->where('item_type', 'previous_period')->count() > 0;
            if (!$hasPreviousItems && $borrowerId) {
                $this->autoCopyFromPreviousPeriod($monitoringNote->id, $borrowerId);
                // Reload action items setelah copy
                $monitoringNote->load('actionItems');
            }
        }

        return $monitoringNote;
    }

    /**
     * Auto-copy action items from previous period
     */
    private function autoCopyFromPreviousPeriod(int $monitoringNoteId, int $borrowerId)
    {
        try {
            $this->actionItemService->copyFromPreviousPeriod($monitoringNoteId, $borrowerId);
        } catch (\Exception $e) {
            Log::warning('Failed to auto-copy from previous period: ' . $e->getMessage());
        }
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

    public function isNawRequired(int $reportId): bool
    {
        $report = Report::with('summary')->findOrFail($reportId);
        
        // NAW diperlukan jika klasifikasi final adalah 'watchlist'
        return $report->summary && 
               $report->summary->final_classification === Classification::WATCHLIST->value;
    }

    /**
     * Validate NAW completion
     */
    public function validateNAWCompletion(MonitoringNote $monitoringNote): array
    {
        $missingItems = [];
        
        // Check monitoring note fields
        if (empty($monitoringNote->watchlist_reason) || trim($monitoringNote->watchlist_reason) === '') {
            $missingItems[] = 'Alasan Watchlist';
        }
        
        if (empty($monitoringNote->account_strategy) || trim($monitoringNote->account_strategy) === '') {
            $missingItems[] = 'Account Strategy';
        }
        
        // Check action items
        $actionItems = $monitoringNote->actionItems;
        
        // Check previous period progress
        $previousItems = $actionItems->where('item_type', 'previous_period');
        $previousItemsWithProgress = $previousItems->filter(function($item) {
            return !empty($item->progress_notes) && trim($item->progress_notes) !== '';
        });
        
        if ($previousItems->count() > 0 && $previousItemsWithProgress->count() < $previousItems->count()) {
            $missingItems[] = 'Progress dari periode sebelumnya';
        }
        
        // Check next period plans
        $nextItems = $actionItems->where('item_type', 'next_period');
        if ($nextItems->count() === 0) {
            $missingItems[] = 'Rencana tindak lanjut periode berikutnya';
        }
        
        return [
            'is_complete' => empty($missingItems),
            'missing_items' => $missingItems,
            'completion_percentage' => $this->calculateCompletionPercentage($monitoringNote)
        ];
    }
    
    /**
     * Calculate completion percentage
     */
    private function calculateCompletionPercentage(MonitoringNote $monitoringNote): int
    {
        $totalItems = 4; // watchlist_reason, account_strategy, previous_progress, next_plan
        $completedItems = 0;
        
        // Check monitoring note fields
        if (!empty($monitoringNote->watchlist_reason) && trim($monitoringNote->watchlist_reason) !== '') {
            $completedItems++;
        }
        
        if (!empty($monitoringNote->account_strategy) && trim($monitoringNote->account_strategy) !== '') {
            $completedItems++;
        }
        
        // Check previous period progress
        $actionItems = $monitoringNote->actionItems;
        $previousItems = $actionItems->where('item_type', 'previous_period');
        $previousItemsWithProgress = $previousItems->filter(function($item) {
            return !empty($item->progress_notes) && trim($item->progress_notes) !== '';
        });
        
        if ($previousItems->count() === 0 || $previousItemsWithProgress->count() === $previousItems->count()) {
            $completedItems++;
        }
        
        // Check next period plans
        $nextItems = $actionItems->where('item_type', 'next_period');
        if ($nextItems->count() > 0) {
            $completedItems++;
        }
        
        return round(($completedItems / $totalItems) * 100);
    }
}