<?php

namespace App\Http\Controllers;

use App\Http\Resources\BorrowerResource;
use App\Services\BorrowerService;
use App\Services\FormService;
use App\Services\PeriodService;
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
}