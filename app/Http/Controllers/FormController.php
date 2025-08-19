<?php

namespace App\Http\Controllers;

use App\Enum\ReportStatus;
use App\Http\Resources\BorrowerResource;
use App\Services\BorrowerService;
use App\Services\FormService;
use App\Services\PeriodService;
use App\Services\ApprovalWorkflowService; // ✅ Tambahkan import
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // ✅ Tambahkan import
use Inertia\Inertia;
use App\Models\Report;
use App\Models\Answer;
use App\Models\BorrowerDetail;
use App\Models\BorrowerFacility;
use App\Services\SummaryCalculationService;
use Illuminate\Support\Facades\DB;

class FormController extends Controller
{
    protected $formService;
    protected $borrowerService;
    protected $periodService;
    protected $summaryService;
    protected $approvalWorkflowService; // ✅ Tambahkan property

    public function __construct(
        FormService $formService,
        BorrowerService $borrowerService,
        PeriodService $periodService,
        SummaryCalculationService $summaryService,
        ApprovalWorkflowService $approvalWorkflowService // ✅ Tambahkan parameter
    ) {
        $this->formService = $formService;
        $this->borrowerService = $borrowerService;
        $this->periodService = $periodService;
        $this->summaryService = $summaryService;
        $this->approvalWorkflowService = $approvalWorkflowService; // ✅ Assign property
    }

    public function index(Request $request)
    {
        $templateId = $request->input('template_id');

        $borrowerData = session('borrower_data', $request->input('borrower_data', []));
        $facilityData = session('facility_data', $request->input('facility_data', []));

        $borrowers = $this->borrowerService->getAllBorrowers();

        // Ubah untuk mendapatkan periode aktif tunggal
        $activePeriod = $this->periodService->getActivePeriod();

        $formData = $this->formService->getFormData($templateId, $borrowerData, $facilityData);

        return Inertia::render('form/Index', [
            'borrowers' => BorrowerResource::collection($borrowers)->resolve(),
            'template_id' => $formData['template_id'],
            'borrower_data' => $formData['borrower_data'],
            'facility_data' => $formData['facility_data'],
            'aspect_groups' => $formData['aspectGroups'],
            'active_period' => $activePeriod
        ]);
    }

    public function submitAll(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validasi yang lebih lengkap
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
                
                'reportMeta.template_id' => 'required|exists:templates,id',
                'reportMeta.period_id' => 'required|exists:periods,id'
            ]);
            
            $borrowerId = $validated['informationBorrower']['borrowerId'];
            
            // 1. Simpan/Update BorrowerDetail
            BorrowerDetail::updateOrCreate(
                ['borrower_id' => $borrowerId],
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
            
            // 2. Simpan facilities
            foreach ($validated['facilitiesBorrower'] as $facility) {
                BorrowerFacility::create([
                    'borrower_id' => $borrowerId,
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
            
            // 3. Buat report baru
            $report = Report::create([
                'borrower_id' => $borrowerId,
                'template_id' => $validated['reportMeta']['template_id'],
                'period_id' => $validated['reportMeta']['period_id'],
                'status' => ReportStatus::DRAFT->value,
                'created_by' => Auth::id() // ✅ Fix auth issue
            ]);
            
            // 4. Simpan jawaban-jawaban aspek
            $answersCreated = 0;
            foreach ($validated['aspectsBorrower'] as $aspectAnswer) {
                if ($aspectAnswer['selectedOptionId']) {
                    Answer::create([
                        'report_id' => $report->id,
                        'question_version_id' => $aspectAnswer['questionId'],
                        'question_option_id' => $aspectAnswer['selectedOptionId'],
                        'notes' => $aspectAnswer['notes'] ?? ''
                    ]);
                    $answersCreated++;
                }
            }
            
            // 5. Calculate summary
            $this->summaryService->calculateAndStoreSummary($report->id);
            
            // ✅ 6. SUBMIT REPORT FOR APPROVAL - INI YANG MENGISI submitted_at
            $this->approvalWorkflowService->submitReport($report, Auth::user());
            
            Log::info('Form submission successful', [
                'report_id' => $report->id,
                'borrower_id' => $borrowerId,
                'answers_created' => $answersCreated,
                'facilities_created' => count($validated['facilitiesBorrower']),
                'status_before_submit' => 'DRAFT',
                'status_after_submit' => $report->fresh()->status->name,
                'submitted_at' => $report->fresh()->submitted_at // ✅ Log submitted_at
            ]);
            
            DB::commit();
            
            // Clear session data
            Session::forget(['borrower_data', 'facility_data', 'current_step', 'borrower_id']);
            
            // Return Inertia response
            return Inertia::location(route('summary', ['reportId' => $report->id]));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Form submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            
            return back()->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function saveStepData(Request $request): JsonResponse
    {
        try {
            $stepType = $request->input('step_type');
            $stepData = $request->input('data', []);
            $templateId = $request->input('template_id');
            $borrowerId = $request->input('borrower_id');
            $periodId = $request->input('period_id');
            $currentStep = $request->input('current_step', 1);

            // Ambil data yang sudah ada dari session
            $existingBorrowerData = session('borrower_data', []);
            $existingFacilityData = session('facility_data', []);
            
            // Konversi data berdasarkan step type
            $borrowerData = $existingBorrowerData;
            $facilityData = $existingFacilityData;
            
            if ($stepType === 'borrower') {
                $borrowerData = array_merge($existingBorrowerData, $stepData);
            } elseif ($stepType === 'facility') {
                $facilityData = $stepData; // Array of facilities
            }

            // Simpan ke session
            Session::put('borrower_data', $borrowerData);
            Session::put('facility_data', $facilityData);
            Session::put('current_step', $currentStep);
            
            if ($borrowerId) {
                Session::put('borrower_id', $borrowerId);
            }

            // Log template sebelum evaluasi
            Log::info('FormController - Before template evaluation', [
                'current_templateId' => $templateId,
                'borrowerData' => $borrowerData,
                'facilityData' => $facilityData
            ]);

            // Dapatkan form data dengan template yang dievaluasi ulang
            // Jangan pass templateId agar FormService mengevaluasi ulang
            $formData = $this->formService->getFormData(null, $borrowerData, $facilityData);

            // Log hasil evaluasi template
            Log::info('FormController - After template evaluation', [
                'old_template_id' => $templateId,
                'new_template_id' => $formData['template_id'],
                'template_changed' => $templateId != $formData['template_id'],
                'aspect_groups_count' => count($formData['aspectGroups'] ?? [])
            ]);

            $response = [
                'success' => true,
                'template_id' => $formData['template_id'],
                'aspect_groups' => $formData['aspectGroups'] ?? [],
                'borrower_data' => $formData['borrower_data'],
                'facility_data' => $formData['facility_data'],
                'message' => 'Data step berhasil disimpan'
            ];
            
            // Tambahkan flag jika template berubah
            if ($templateId && $templateId != $formData['template_id']) {
                $response['template_changed'] = true;
                $response['message'] = 'Data step berhasil disimpan dan template berubah';
            }

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('FormController saveStepData error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data step: ' . $e->getMessage()
            ], 500);
        }
    }
}