<?php

namespace App\Services;

use App\Enum\ActionItemStatus;
use App\Models\MonitoringNote;
use Illuminate\Support\Collection;

class MonitoringStatService
{
    public function getBorrowerStats(int $borrowerId): array
    {
        $monitoringNotes = MonitoringNote::whereHas('watchlist', function ($query) use ($borrowerId) {
            $query->where('borrower_id', $borrowerId);
        })
        ->with('actionItems')
        ->get();
        
        $allActionItems = $monitoringNotes->flatMap->actionItems;
        
        return [
            'total_monitoring_notes' => $monitoringNotes->count(),
            'total_action_items' => $allActionItems->count(),
            'completed_items' => $allActionItems->where('status', ActionItemStatus::COMPLETED->value)->count(),
            'overdue_items' => $allActionItems->where('status', ActionItemStatus::OVERDUE->value)->count(),
            'pending_items' => $allActionItems->where('status', ActionItemStatus::PENDING->value)->count(),
            'completion_rate' => $this->calculateCompletionRate($allActionItems)
        ];
    }

    private function calculateCompletionRate(Collection $actionItems): float
    {
        $total = $actionItems->count();
        $completed = $actionItems->where('status', ActionItemStatus::COMPLETED->value)->count();
        
        return $total > 0 ? round(($completed / $total) * 100, 2) : 0;
    }
}