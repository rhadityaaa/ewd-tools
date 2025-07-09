<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExtendPeriodRequest;
use App\Http\Requests\StorePeriodRequest;
use App\Http\Requests\UpdatePeriodRequest;
use App\Http\Resources\PeriodResource;
use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodController extends Controller
{
    protected PeriodService $periodService;

    public function __construct(PeriodService $periodService)
    {
        $this->periodService = $periodService;
    }

    public function index()
    {
        $periods = $this->periodService->getAllPeriods();

        return Inertia::render('period/Index', [
            'periods' => PeriodResource::collection($periods)->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('period/Create');
    }

    public function store(StorePeriodRequest $request): RedirectResponse
    {
        $this->periodService->createPeriod($request->validated());

        return redirect()->route('periods.index')->with('success', 'Period created successfully.');
    }

    public function show(int $id): Response
    {
        $period = $this->periodService->getPeriodById($id);

        return Inertia::render('period/Show', [
            'period' => new PeriodResource($period)->resolve(),
        ]);
    }

    public function edit(Period $period): Response
    {
        return Inertia::render('period/Edit', [
            'period' => new PeriodResource($period)->resolve(),
        ]);
    }

    public function update(UpdatePeriodRequest $request, Period $period): RedirectResponse
    {
        $validated = $request->validated();

        $this->periodService->updatePeriod($period, $validated);

        return redirect()->route('periods.index')->with('success', 'Period updated successfully.');
    }

    public function destroy(Period $period): RedirectResponse
    {
        $this->periodService->deletePeriod($period);

        return redirect()->route('periods.index')->with('success', 'Period deleted successfully.');
    }

    public function start(Period $period): RedirectResponse
    {
        $this->periodService->markAsActive($period);

        return redirect()->route('periods.index')->with('success', 'Period started successfully.');
    }

    public function stop(Period $period): RedirectResponse
    {
        $this->periodService->markAsEnded($period);

        return redirect()->route('periods.index')->with('success', 'Period ended successfully.');
    }

    public function extend(ExtendPeriodRequest $request, Period $period): RedirectResponse
    {
        $combinedEndDate = $this->periodService->combineDateTime(
            $request->validated('end_date'),
            $request->validated('end_time') ?? '23:59:59'
        );
        $this->periodService->extendPeriod($period, $combinedEndDate->format('Y-m-d H:i:s'));

        return redirect()->route('periods.index')->with('success', 'Period extended successfully.');
    }

    public function checkExpiredPeriods(): RedirectResponse
    {
        $this->periodService->checkAndMarkExpiredPeriods();

        return redirect()->route('periods.index')->with('success', 'Expired periods checked and updated.');
    }
}
