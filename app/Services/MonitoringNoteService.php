<?php

namespace App\Services;

use App\Enum\Classification;
use App\Models\MonitoringNote;
use App\Models\Report;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Auth;

class MonitoringNoteService
{
    public function isNawRequired(int $reportId): bool
    {
        $report = Report::with('summary')->findOrFail($reportId);

        $currentClassification = $report->summary->final_classification;
        $previousClassification = $this->getPreviousPeriodClassification($report);

        return $currentClassification === Classification::WATCHLIST->value || $previousClassification === Classification::WATCHLIST->value;
    }

    public function getPreviousPeriodClassification(Report $report): ?string
    {
        $previousReport = Report::where('borrower_id', $report->borrower_id)
            ->where('period_id', '<', $report->period_id)
            ->with('summary')
            ->orderBy('period_id', 'desc')
            ->first();
            
        if (!$previousReport || !$previousReport->summary) {
            return null;
        }
            
        return $previousReport->summary->final_classification;
    }

     public function getOrCreateMonitoringNote(int $reportId): MonitoringNote
    {
        $report = Report::findOrFail($reportId);
        
        $watchlist = Watchlist::firstOrCreate(
            ['report_id' => $reportId, 'borrower_id' => $report->borrower_id],
            ['status' => 'active', 'added_by' => Auth::id()]
        );

        $monitoringNote = MonitoringNote::firstOrCreate(
            ['watchlist_id' => $watchlist->id],
            [
                'watchlist_reason' => '(Alasan belum diisi)',
                'account_strategy' => '(Strategi belum diisi)',
                'created_by' => Auth::id(),
            ]
        );
        
        return $monitoringNote->loadMissing(['actionItems', 'createdBy', 'updatedBy']);
    }
    
    public function updateMonitoringNote(int $monitoringNoteId, array $data): MonitoringNote
    {
        $monitoringNote = MonitoringNote::findOrFail($monitoringNoteId);
        
        $updateData = [];

        if (array_key_exists('watchlist_reason', $data)) {
            $updateData['watchlist_reason'] = $data['watchlist_reason'];
        }

        if (array_key_exists('account_strategy', $data)) {
            $updateData['account_strategy'] = $data['account_strategy'];
        }

        if (empty($updateData)) {
            return $monitoringNote;
        }

        $updateData['updated_by'] = Auth::id();
        
        $monitoringNote->update($updateData);
        
        return $monitoringNote->fresh(['actionItems', 'createdBy', 'updatedBy']);
    }
}