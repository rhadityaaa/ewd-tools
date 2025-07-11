<?php

namespace App\Http\Controllers;

use App\Http\Requests\DivisionRequest;
use App\Http\Resources\DivisionResource;
use App\Services\DivisionService;
use Inertia\Inertia;

class DivisionController extends Controller
{
    protected $divisionService;

    public function __construct(DivisionService $divisionService)
    {
        $this->divisionService = $divisionService;
    }

    public function index()
    {
        $divisions = $this->divisionService->getAllDivisions();

        return Inertia::render('division/Index', [
            'divisions' => DivisionResource::collection($divisions)->resolve(),
        ]);
    }

    public function create()
    {
        return Inertia::render('division/Create');
    }

    public function store(DivisionRequest $request)
    {
        $this->divisionService->createDivision($request->validated());

        return redirect()->route('divisions.index')->with('success', 'Division created successfully.');
    }

    public function show(int $id)
    {
        $division = $this->divisionService->getDivisionById($id);

        return Inertia::render('division/Show', [
            'division' => new DivisionResource($division)->resolve(),
        ]);
    }

    public function edit(int $id)
    {
        $division = $this->divisionService->getDivisionById($id);

        return Inertia::render('division/Edit', [
            'division' => new DivisionResource($division)->resolve(),
        ]);
    }

    public function update(DivisionRequest $request, int $id)
    {
        $division = $this->divisionService->getDivisionById($id);

        $this->divisionService->updateDivision($division, $request->validated());

        return redirect()->route('divisions.index')->with('success', 'Division updated successfully.');
    }
    
    public function destroy(int $id)
    {
        $division = $this->divisionService->getDivisionById($id);

        $this->divisionService->deleteDivision($division);

        return redirect()->route('divisions.index')->with('success', 'Division deleted successfully.');
    }
}
