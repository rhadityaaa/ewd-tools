<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActionItemResource;
use App\Http\Resources\MonitoringNoteResource;
use App\Http\Resources\ReportResource;
use App\Models\MonitoringNote;
use App\Models\ActionItem;
use App\Models\Watchlist;
use App\Models\Report;
use App\Services\MonitoringNoteService;
use App\Services\ActionItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringNoteController extends Controller
{
    protected MonitoringNoteService $monitoringNoteService;
    protected ActionItemService $actionItemService;

    public function __construct(
        MonitoringNoteService $monitoringNoteService,
        ActionItemService $actionItemService
    )
    {
        $this->monitoringNoteService = $monitoringNoteService;
        $this->actionItemService = $actionItemService;
    }

    public function show(Request $request): Response
    {
        $reportId = $request->query('report_id');
        
        if (!$reportId) {
            abort(400, 'Report ID is required');
        }

        $report = Report::with(['borrower', 'period', 'summary'])->findOrFail($reportId);
        
        // Check if NAW is required
        $isNawRequired = $this->monitoringNoteService->isNawRequired($reportId);
        
        if (!$isNawRequired) {
            // return redirect()->back()->with('error', 'NAW tidak diperlukan untuk laporan ini.');
        }

        // Get or create monitoring note
        $monitoringNote = $this->monitoringNoteService->getOrCreateMonitoringNote($reportId);
        
        // Get action items grouped by type
        $actionItems = ActionItem::where('monitoring_note_id', $monitoringNote->id)
            ->orderBy('item_type')
            ->orderBy('created_at')
            ->get()
            ->groupBy('item_type');

        // Get watchlist if exists
        $watchlist = Watchlist::where('report_id', $reportId)->first();

        return Inertia::render('Watchlist', [
            'report' => new ReportResource($report),
            'monitoringNote' => new MonitoringNoteResource($monitoringNote->load(['createdBy', 'updatedBy'])),
            'actionItems' => $actionItems->map(function ($item) {
                return ActionItemResource::collection($item);
            }),
            'watchlist' => $watchlist,
            'isNawRequired' => $isNawRequired,
            // 'canEdit' => $this->monitoringNoteService->canAccessNaw($reportId)
        ]);
    }

    /**
     * Update monitoring note
     */
    public function update(Request $request, MonitoringNote $monitoringNote)
    {
        $validated = $request->validate([
            'watchlist_reason' => 'nullable|string|max:1000',
            'account_strategy' => 'nullable|string|max:1000',
        ]);

        $validated['updated_by'] = Auth::id();
        
        $monitoringNote->update($validated);

        return back()->with('success', 'Monitoring note berhasil diperbarui.');
    }

    /**
     * Store new action item
     */
    public function storeActionItem(Request $request, MonitoringNote $monitoringNote)
    {
        $validated = $request->validate([
            'action_description' => 'required|string|max:500',
            'item_type' => 'required|in:previous_period,current_progress,next_period',
            'progress_notes' => 'nullable|string|max:1000',
            'people_in_charge' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        $actionItem = $this->actionItemService->createActionItem($monitoringNote->id, $validated);

        return back()->with('success', 'Action item berhasil ditambahkan.');
    }

    /**
     * Update action item
     */
    public function updateActionItem(Request $request, ActionItem $actionItem)
    {
        $validated = $request->validate([
            'action_description' => 'required|string|max:500',
            'progress_notes' => 'nullable|string|max:1000',
            'people_in_charge' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'due_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
            'status' => 'required|in:pending,in_progress,completed,overdue'
        ]);

        $this->actionItemService->updateActionItem($actionItem->id, $validated);

        return back()->with('success', 'Action item berhasil diperbarui.');
    }

    /**
     * Delete action item
     */
    public function destroyActionItem(ActionItem $actionItem)
    {
        $this->actionItemService->deleteActionItem($actionItem->id);

        return back()->with('success', 'Action item berhasil dihapus.');
    }

    /**
     * Copy action items from previous period
     */
    public function copyFromPrevious(Request $request, MonitoringNote $monitoringNote)
    {
        // Get report through watchlist relationship
        $watchlist = $monitoringNote->watchlist;
        if (!$watchlist || !$watchlist->report) {
            return back()->with('error', 'Report tidak ditemukan.');
        }

        $report = $watchlist->report;

        $copiedItems = $this->actionItemService->copyFromPreviousPeriod(
            $monitoringNote->id, 
            $report->borrower_id
        );

        if ($copiedItems->count() > 0) {
            return back()->with('success', "Berhasil menyalin {$copiedItems->count()} action item dari periode sebelumnya.");
        }

        return back()->with('info', 'Tidak ada action item dari periode sebelumnya untuk disalin.');
    }

    /**
     * Update watchlist status
     */
    public function updateWatchlistStatus(Request $request, MonitoringNote $monitoringNote)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,resolved,escalated',
            'resolver_notes' => 'nullable|string|max:1000'
        ]);

        // Get watchlist through relationship
        $watchlist = $monitoringNote->watchlist;
        
        if (!$watchlist) {
            return back()->with('error', 'Watchlist tidak ditemukan.');
        }

        $updateData = ['status' => $validated['status']];
        
        if ($validated['status'] === 'resolved') {
            $updateData['resolved_by'] = Auth::id();
            $updateData['resolved_at'] = now();
            $updateData['resolved_notes'] = $validated['resolver_notes'] ?? null;
        } else {
            $updateData['resolved_by'] = null;
            $updateData['resolved_at'] = null;
            $updateData['resolved_notes'] = null;
        }

        $watchlist->update($updateData);

        return back()->with('success', 'Status watchlist berhasil diperbarui.');
    }

    /**
     * Check NAW access via AJAX
     */
    public function checkAccess(Request $request)
    {
        $reportId = $request->query('report_id');
        
        if (!$reportId) {
            return response()->json(['error' => 'Report ID is required'], 400);
        }

        $isRequired = $this->monitoringNoteService->isNawRequired($reportId);
        // $canAccess = $this->monitoringNoteService->canAccessNaw($reportId);

        return response()->json([
            'is_required' => $isRequired,
            // 'can_access' => $canAccess   
        ]);
    }
}