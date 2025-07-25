<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with([
            'borrower:id,name',
            'template:id,name',
            'period:id,name,start_date,end_date',
            'creator:id,name',
            'summary:id,report_id,final_classification,indicative_collectibility'
        ]);

        // Filter berdasarkan periode jika ada
        if ($request->filled('period_id')) {
            $query->where('period_id', $request->period_id);
        }

        // Filter berdasarkan borrower jika ada
        if ($request->filled('borrower_id')) {
            $query->where('borrower_id', $request->borrower_id);
        }

        // Filter berdasarkan klasifikasi jika ada
        if ($request->filled('classification')) {
            $query->whereHas('summary', function ($q) use ($request) {
                $q->where('final_classification', $request->classification);
            });
        }

        // Search berdasarkan nama borrower
        if ($request->filled('search')) {
            $query->whereHas('borrower', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        $reports = $query->orderBy('created_at', 'desc')
                        ->paginate(15)
                        ->withQueryString();

        // Data untuk filter dropdown
        $periods = \App\Models\Period::select('id', 'name', 'start_date', 'end_date')
                                    ->orderBy('start_date', 'desc')
                                    ->get();

        $borrowers = \App\Models\Borrower::select('id', 'name')
                                        ->orderBy('name')
                                        ->get();

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
            'periods' => $periods,
            'borrowers' => $borrowers,
            'filters' => $request->only(['period_id', 'borrower_id', 'classification', 'search'])
        ]);
    }

    public function show(Report $report)
    {
        $report->load([
            'borrower',
            'template',
            'period',
            'creator',
            'summary',
            'reportAspects.aspectVersion'
        ]);

        return Inertia::render('Reports/Show', [
            'report' => $report
        ]);
    }

    public function destroy(Report $report)
    {
        try {
            $report->delete();
            
            return redirect()->route('reports.index')
                           ->with('success', 'Report berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->route('reports.index')
                           ->with('error', 'Gagal menghapus report: ' . $e->getMessage());
        }
    }
}