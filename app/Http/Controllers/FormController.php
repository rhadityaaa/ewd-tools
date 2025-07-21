<?php

namespace App\Http\Controllers;

use App\Enum\Status;
use App\Models\Answer;
use App\Models\BorrowerFacility;
use App\Models\Period;
use App\Models\Report;
use App\Models\ReportAspect;
use App\Services\FormService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class FormController extends Controller
{
    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;     
    }

    public function index(Request $request)
    {
        // Ambil data dari sesi jika ada
        $borrowerData = session('borrower_data', []);
        $facilityData = session('facility_data', []);
        
        Log::info('Form index - session data:', [
            'borrower_data' => $borrowerData,
            'facility_data' => $facilityData
        ]);
        
        // Check if template_id is passed via request (for direct navigation)
        $templateId = $request->input('template_id');
        
        // Tentukan template berdasarkan data yang ada jika tidak ada template_id
        if (!$templateId && !empty($borrowerData) && !empty($facilityData)) {
            $templateId = $this->formService->getApplicableTemplate($borrowerData, $facilityData);
            Log::info('Template determined from session data:', ['template_id' => $templateId]);
        }
        
        // Also check if data is passed via request (for direct navigation)
        if ($request->has('borrower_data')) {
            $borrowerData = $request->input('borrower_data');
            session(['borrower_data' => $borrowerData]);
        }
        
        if ($request->has('facility_data')) {
            $facilityData = $request->input('facility_data');
            session(['facility_data' => $facilityData]);
            
            // Re-evaluate template if facility data is provided
            if (!empty($borrowerData)) {
                $templateId = $this->formService->getApplicableTemplate($borrowerData, $facilityData);
                Log::info('Template re-evaluated after facility data:', ['template_id' => $templateId]);
            }
        }
        
        $formData = $this->formService->getFormData($templateId, $borrowerData, $facilityData);
    
        Log::info('Form data prepared:', [
            'template_id' => $templateId,
            'aspect_groups_count' => count($formData['aspectGroups'] ?? [])
        ]);

        return Inertia::render('form/Index', $formData);
    }

    // Add method to save step data to session
    public function saveStepData(Request $request)
    {
        $stepType = $request->input('step_type'); // 'borrower' or 'facility'
        $data = $request->input('data');
        
        Log::info('Saving step data to session', [
            'step_type' => $stepType,
            'data' => $data
        ]);
        
        if ($stepType === 'borrower') {
            session(['borrower_data' => $data]);
            Log::info('Borrower data saved to session', ['borrower_data' => session('borrower_data')]);
        } elseif ($stepType === 'facility') {
            session(['facility_data' => $data]);
            Log::info('Facility data saved to session', ['facility_data' => session('facility_data')]);
            
            // Tentukan template berdasarkan data borrower dan facility
            $borrowerData = session('borrower_data', []);
            $facilityData = $data;
            
            if (!empty($borrowerData) && !empty($facilityData)) {
                $templateId = $this->formService->getApplicableTemplate($borrowerData, $facilityData);
                Log::info('Template determined after facility data:', ['template_id' => $templateId]);
                
                return response()->json([
                    'success' => true,
                    'template_id' => $templateId,
                    'redirect_url' => route('forms.index', ['template_id' => $templateId])
                ]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    public function submitAll(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $validated = $request->validate([
                'informationBorrower' => 'required|array',
                'facilitiesBorrower' => 'required|array',
                'aspectsBorrower' => 'required|array',
                'reportMeta' => 'required|array'
            ]);
            
            // Logging untuk debugging
            Log::info('Submit data received:', [
                'reportMeta' => $validated['reportMeta'],
                'template_id' => $validated['reportMeta']['template_id'] ?? 'null',
                'period_id' => $validated['reportMeta']['period_id'] ?? 'null'
            ]);
            
            // Pastikan template_id dan period_id tidak null
            $templateId = $validated['reportMeta']['template_id'] ?? null;
            $periodId = $validated['reportMeta']['period_id'] ?? null;
            
            // Jika template_id kosong, coba tentukan dari session data
            if (!$templateId) {
                $borrowerData = session('borrower_data', []);
                $facilityData = session('facility_data', []);
                
                if (!empty($borrowerData) && !empty($facilityData)) {
                    $templateId = $this->formService->getApplicableTemplate($borrowerData, $facilityData);
                    Log::info('Template determined from session:', ['template_id' => $templateId]);
                }
            }
            
            if (!$templateId) {
                // Return Inertia response dengan error
                return back()->withErrors([
                    'template' => 'Template ID tidak boleh kosong. Pastikan template telah dipilih berdasarkan aturan visibilitas.'
                ]);
            }
            
            if (!$periodId) {
                // Fallback ke active period jika period_id tidak ada
                $activePeriod = Period::where('status', Status::ACTIVE)
                                      ->where('start_date', '<=', now())
                                      ->where('end_date', '>=', now())
                                      ->first();
                
                if (!$activePeriod) {
                    return back()->withErrors([
                        'period' => 'Tidak ada periode aktif yang tersedia.'
                    ]);
                }
                
                $periodId = $activePeriod->id;
            }
            
            $report = Report::create([
                'borrower_id' => $validated['informationBorrower']['borrowerId'],
                'template_id' => $templateId,
                'period_id' => $periodId,
                'status' => 'draft'
            ]);
            
            foreach ($validated['facilitiesBorrower'] as $facility) {
                BorrowerFacility::create([
                    'report_id' => $report->id,
                    'facility_name' => $facility['facilityName'],
                    'limit' => $facility['limit'],
                    'outstanding' => $facility['outstanding'],
                ]);
            }
            
            // Simpan jawaban individual ke table answers
            foreach ($validated['aspectsBorrower'] as $aspect) {
                Answer::create([
                    'report_id' => $report->id,
                    'question_version_id' => $aspect['questionId'],
                    'question_option_id' => $aspect['selectedOptionId'],
                    'notes' => $aspect['notes'] ?? null,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('summary')->with('success', 'Data berhasil disimpan');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Form submission error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'submission' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }
}
