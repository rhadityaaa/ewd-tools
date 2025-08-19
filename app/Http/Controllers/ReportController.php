<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Models\Borrower;
use App\Models\Period;
use App\Models\Report;
use App\Services\ReportService;
use App\Services\FormService;
use App\Services\BorrowerService;
use App\Services\PeriodService;
use App\Services\ApprovalWorkflowService;
use App\Enum\ReportStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    protected $reportService;
    protected $formService;
    protected $borrowerService;
    protected $periodService;
    protected $approvalWorkflowService;

    public function __construct(
        ReportService $reportService,
        FormService $formService,
        BorrowerService $borrowerService,
        PeriodService $periodService,
        ApprovalWorkflowService $approvalWorkflowService
    ) {
        $this->reportService = $reportService;
        $this->formService = $formService;
        $this->borrowerService = $borrowerService;
        $this->periodService = $periodService;
        $this->approvalWorkflowService = $approvalWorkflowService;
    }

    public function index()
    {
        $user = Auth::user();
        
        // Get reports based on user role
        if ($user->hasRole('unit_bisnis')) {
            // Unit bisnis only sees their own reports
            $reports = Report::with(['borrower', 'period', 'creator'])
                ->where('created_by', $user->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            // Other roles see all reports
            $reports = $this->reportService->getAllReport();
        }

        return Inertia::render('report/Index', [
            'reports' => ReportResource::collection($reports),
        ]);
    }

    public function show(int $id)
    {
        $report = $this->reportService->getReportById($id);
        
        // Check if user can view this report
        $user = Auth::user();
        if ($user->hasRole('unit_bisnis') && $report->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk melihat laporan ini.');
        }

        return Inertia::render('report/Show', [
            'report' => new ReportResource($report),
        ]);
    }

    /**
     * Show edit form for rejected reports
     */
    public function edit(int $id)
    {
        $user = Auth::user();
        $report = Report::with([
            'borrower.details',
            'borrower.facilities',
            'period',
            'template',
            'answers.questionVersion.question',
            'answers.questionOption'
        ])->findOrFail($id);
        
        // Check if user can edit this report
        if (!$user->hasRole('unit_bisnis') || $report->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit laporan ini.');
        }
        
        // Check if report can be edited (DRAFT, REJECTED, REVISION_REQUIRED, or SUBMITTED/PENDING)
        $editableStatuses = [
            ReportStatus::DRAFT,
            ReportStatus::REJECTED, 
            ReportStatus::REVISION_REQUIRED,
            ReportStatus::SUBMITTED // Allow editing pending reports
        ];
        
        if (!in_array($report->status, $editableStatuses)) {
            return redirect()->route('reports.show', $id)
                ->with('error', 'Laporan ini tidak dapat diedit. Status: ' . $report->status->name);
        }
        
        // Get form data for editing
        $borrowerData = [
            'borrower_id' => $report->borrower_id,
            'borrower_group' => $report->borrower->borrowerDetail->borrower_group ?? '',
            'purpose' => $report->borrower->borrowerDetail->purpose ?? 'kie',
            'economic_sector' => $report->borrower->borrowerDetail->economic_sector ?? '',
            'business_field' => $report->borrower->borrowerDetail->business_field ?? '',
            'borrower_business' => $report->borrower->borrowerDetail->borrower_business ?? '',
            'collectibility' => $report->borrower->borrowerDetail->collectibility ?? 1,
            'restructuring' => $report->borrower->borrowerDetail->restructuring ?? false,
        ];
        
        $facilityData = $report->borrower->facilities->map(function ($facility) {
            return [
                'id' => $facility->id,
                'name' => $facility->facility_name,
                'limit' => $facility->limit,
                'outstanding' => $facility->outstanding,
                'interest_rate' => $facility->interest_rate,
                'principal_arrears' => $facility->principal_arrears,
                'interest_arrears' => $facility->interest_arrears,
                'pdo' => $facility->pdo_days,
                'maturity_date' => $facility->maturity_date->format('Y-m-d'),
            ];
        })->toArray();
        
        // Get form structure
        $formData = $this->formService->getFormData($report->template_id, $borrowerData, $facilityData);
        
        // Map existing answers
        $existingAnswers = [];
        foreach ($report->answers as $answer) {
            $existingAnswers[$answer->question_version_id] = [
                'selectedOptionId' => $answer->question_option_id,
                'notes' => $answer->notes ?? ''
            ];
        }
        
        return Inertia::render('report/Edit', [
            'report' => new ReportResource($report),
            'formData' => $formData,
            'existingAnswers' => $existingAnswers,
            'borrowers' => $this->borrowerService->getAllBorrowers(),
            'activePeriod' => $this->periodService->getActivePeriod(),
            'rejectionReason' => $report->rejection_reason,
        ]);
    }
    
    /**
     * Update rejected report
     */
    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $report = Report::findOrFail($id);
        
        // Check permissions
        if (!$user->hasRole('unit_bisnis') || $report->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengupdate laporan ini.');
        }
        
        // Check if report can be edited
        $editableStatuses = [
            ReportStatus::DRAFT,
            ReportStatus::REJECTED, 
            ReportStatus::REVISION_REQUIRED,
            ReportStatus::SUBMITTED // Allow editing pending reports
        ];
        
        if (!in_array($report->status, $editableStatuses)) {
            return redirect()->back()->with('error', 'Laporan ini tidak dapat diedit.');
        }
        
        try {
            DB::beginTransaction();
            
            // Validate request
            $validated = $request->validate([
                'informationBorrower.borrowerId' => 'required|exists:borrowers,id',
                'informationBorrower.borrowerGroup' => 'required|string',
                'informationBorrower.purpose' => 'required|in:both,kie,kmke',
                'informationBorrower.economicSector' => 'required|string',
                'informationBorrower.businessField' => 'required|string',
                'informationBorrower.borrowerBusiness' => 'required|string',
                'informationBorrower.collectibility' => 'required|integer|min:1|max:5',
                'informationBorrower.restructuring' => 'required|boolean',
                
                'facilitiesBorrower' => 'required|array|min:1',
                'facilitiesBorrower.*.name' => 'required|string',
                'facilitiesBorrower.*.limit' => 'required|numeric|min:0',
                'facilitiesBorrower.*.outstanding' => 'required|numeric|min:0',
                'facilitiesBorrower.*.interestRate' => 'required|numeric|min:0',
                'facilitiesBorrower.*.principalArrears' => 'required|numeric|min:0',
                'facilitiesBorrower.*.interestArrears' => 'required|numeric|min:0',
                'facilitiesBorrower.*.pdo' => 'required|integer|min:0',
                'facilitiesBorrower.*.maturityDate' => 'required|date',
                
                'aspectsBorrower' => 'required|array|min:1',
                'aspectsBorrower.*.questionId' => 'required|exists:question_versions,id',
                'aspectsBorrower.*.selectedOptionId' => 'required|exists:question_options,id',
            ]);
            
            // Update borrower details
            $report->borrower->details()->updateOrCreate(
                ['borrower_id' => $report->borrower_id],
                [
                    'borrower_group' => $validated['informationBorrower']['borrowerGroup'],
                    'purpose' => $validated['informationBorrower']['purpose'],
                    'economic_sector' => $validated['informationBorrower']['economicSector'],
                    'business_field' => $validated['informationBorrower']['businessField'],
                    'borrower_business' => $validated['informationBorrower']['borrowerBusiness'],
                    'collectibility' => $validated['informationBorrower']['collectibility'],
                    'restructuring' => $validated['informationBorrower']['restructuring'],
                ]
            );
            
            // Update facilities (delete old, create new)
            $report->borrower->facilities()->delete();
            foreach ($validated['facilitiesBorrower'] as $facility) {
                $report->borrower->facilities()->create([
                    'facility_name' => $facility['name'],
                    'limit' => $facility['limit'],
                    'outstanding' => $facility['outstanding'],
                    'interest_rate' => $facility['interestRate'],
                    'principal_arrears' => $facility['principalArrears'],
                    'interest_arrears' => $facility['interestArrears'],
                    'pdo_days' => $facility['pdo'],
                    'maturity_date' => $facility['maturityDate'],
                ]);
            }
            
            // Update answers
            $report->answers()->delete();
            foreach ($validated['aspectsBorrower'] as $aspectAnswer) {
                if ($aspectAnswer['selectedOptionId']) {
                    $report->answers()->create([
                        'question_version_id' => $aspectAnswer['questionId'],
                        'question_option_id' => $aspectAnswer['selectedOptionId'],
                        'notes' => $aspectAnswer['notes'] ?? ''
                    ]);
                }
            }
            
            // Reset report status and clear rejection reason
            $report->update([
                'status' => ReportStatus::DRAFT,
                'rejection_reason' => null,
            ]);
            
            // Delete existing approvals
            $report->approvals()->delete();
            
            DB::commit();
            
            return redirect()->route('reports.show', $id)
                ->with('success', 'Laporan berhasil diupdate. Silakan submit ulang untuk approval.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Gagal mengupdate laporan: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Resubmit updated report for approval
     */
    public function resubmit(int $id)
    {
        $user = Auth::user();
        $report = Report::findOrFail($id);
        
        // Check permissions
        if (!$user->hasRole('unit_bisnis') || $report->created_by !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk submit laporan ini.');
        }
        
        // Check if report can be submitted
        if (!$report->status->canBeSubmitted()) {
            return redirect()->back()->with('error', 'Laporan ini tidak dapat disubmit.');
        }
        
        try {
            // Submit for approval
            $this->approvalWorkflowService->submitReport($report, $user);
            
            return redirect()->route('reports.index')
                ->with('success', 'Laporan berhasil disubmit ulang untuk approval.');
                
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        $report = $this->reportService->getReportById($id);

        $this->reportService->deleteReport($report);

        return redirect()->route('reports.index')->with('success', 'Report deleted successfully.');
    }
}