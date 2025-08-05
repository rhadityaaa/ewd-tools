<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Models\Borrower;
use App\Models\Period;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        $reports = $this->reportService->getAllReport();

        return Inertia::render('report/Index', [
            'reports' => ReportResource::collection($reports),
        ]);
    }

    public function show(int $id)
    {
        $report = $this->reportService->getReportById($id);

        return Inertia::render('report/Show', [
            'report' => new ReportResource($report),
        ]);
    }

    public function destroy(int $id)
    {
        $report = $this->reportService->getReportById($id);

        $this->reportService->deleteReport($report);

        return redirect()->route('reports.index')->with('success', 'Report deleted successfully.');
    }
}