<?php

namespace App\Http\Controllers;

use App\Http\Resources\BorrowerResource;
use App\Services\BorrowerService;
use App\Services\FormService;
use App\Services\PeriodService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FormController extends Controller
{
    protected $formService;
    protected $borrowerService;
    protected $periodService;

    public function __construct(
        FormService $formService,
        BorrowerService $borrowerService,
        PeriodService $periodService
    ) {
        $this->formService = $formService;
        $this->borrowerService = $borrowerService;
        $this->periodService = $periodService;
    }

    public function index(Request $request)
    {
        $templateId = $request->input('template_id');

        $borrowerData = session('borrower_data', $request->input('borrower_data', []));
        $facilityData = session('facility_data', $request->input('facility_data', []));

        $borrowers = $this->borrowerService->getAllBorrowers();

        $activePeriod = $this->periodService->getActivePeriods();

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