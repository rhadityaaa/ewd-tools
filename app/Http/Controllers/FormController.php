<?php

namespace App\Http\Controllers;

use App\Models\BorrowerFacility;
use App\Models\Report;
use App\Models\ReportAspect;
use App\Services\FormService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FormController extends Controller
{
    protected FormService $formService;

    public function __construct(FormService $formService)
    {
        $this->formService = $formService;     
    }

    public function index()
    {
        $formData = $this->formService->getFormData();

        return Inertia::render('form/Index', $formData);
    }

    public function submitAll(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validasi data
            $validated = $request->validate([
                'informationBorrower' => 'required|array',
                'facilitiesBorrower' => 'required|array',
                'aspectsBorrower' => 'required|array',
                'reportMeta' => 'required|array'
            ]);
            
            // Simpan data ke database
            $report = Report::create([
                'borrower_id' => $validated['informationBorrower']['borrowerId'],
                'template_id' => $validated['reportMeta']['templateId'],
                'period_id' => 1,
                'status' => 'draft'
            ]);
            
            // Simpan fasilitas
            foreach ($validated['facilitiesBorrower'] as $facility) {
                BorrowerFacility::create([
                    'report_id' => $report->id,
                    'facility_name' => $facility['facilityName'],
                    'limit' => $facility['limit'],
                    'outstanding' => $facility['outstanding'],
                ]);
            }
            
            // Simpan aspek
            foreach ($validated['aspectsBorrower'] as $aspect) {
                ReportAspect::create([
                    'report_id' => $report->id,
                    'question_id' => $aspect['questionId'],
                    'selected_option_id' => $aspect['selectedOptionId'],
                    'notes' => $aspect['notes'],
                    'score' => $aspect['score']
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('summary', ['reportId' => $report->id])
                ->with('success', 'Data berhasil disimpan');
                
        } catch (Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }
}
