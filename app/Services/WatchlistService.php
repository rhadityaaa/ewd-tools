<?php

namespace App\Services;

use App\Enum\WatchlistStatus;
use App\Models\Report;
use App\Models\Watchlist;
use Illuminate\Support\Facades\Auth;

class WatchlistService
{
    public function getOrCreateWatchlist(Report $report): Watchlist
    {
        $watchlist = Watchlist::where('borrower_id', $report->borrower_id)
            ->where('status', WatchlistStatus::ACTIVE->value)
            ->first();
            
        if (!$watchlist) {
            $watchlist = Watchlist::create([
                'borrower_id' => $report->borrower_id,
                'report_id' => $report->id,
                'status' => WatchlistStatus::ACTIVE->value,
                'added_by' => Auth::id()
            ]);
        }
        
        return $watchlist;
    }

     public function resolveWatchlist(int $watchlistId, string $notes): Watchlist
    {
        $watchlist = Watchlist::findOrFail($watchlistId);
        
        $watchlist->update([
            'status' => WatchlistStatus::RESOLVED->value,
            'resolved_by' => Auth::id(),
            'resolved_notes' => $notes,
            'resolved_at' => now()
        ]);
        
        return $watchlist;
    }
}