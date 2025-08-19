<?php

namespace App\Http\Controllers;

use App\Enum\ApprovalStatus;
use App\Http\Resources\WorkflowResource;
use App\Models\Report;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    protected WorkflowService $workflowService;

    public function __construct(WorkflowService $workflowService)
    {
        $this->workflowService = $workflowService;
    }

    public function show(Report $report)
    {
        $user = Auth::user();

        if (!$this->workflowService->canViewReport($report, $user)) {
            abort(403, 'Anda tidak memiliki akses untuk melihat laporan ini.');
        }

        $report->load([
            'borrower:id,name,division_id',
            'borrower.division:id,name,code',
            'borrower.details',
            'borrower.facilities',
            'period:id,name,start_date,end_date',
            'template:id,name',
            'creator:id,name,email',
            'summary',
            'aspects.aspectVersion.aspect',
            'aspects.aspectVersion.questionVersions.questionOptions',
            'answers.questionVersion.question',
            'answers.questionOption',
            'approvals.assigned:id,name',
            'approvalHistory.user:id,name'
        ]);

        $workflowSteps = $this->workflowService->getApprovalProgress($report);
        $approvalHistory = $this->workflowService->getApprovalHistory($report);

        $currentApproval = $report->approvals()->where('status', ApprovalStatus::PENDING)->first();

        return Inertia::render('workflow/Show', [
            'workflow' => new WorkflowResource($report),
        ]);
    }
}
