<?php

namespace App\Http\Controllers;

use App\Http\Requests\BorrowerRequest;
use App\Http\Resources\BorrowerResource;
use App\Http\Resources\DivisionResource;
use App\Services\BorrowerService;
use App\Services\DivisionService;
use Inertia\Inertia;

class BorrowerController extends Controller
{
    public function __construct(
        protected BorrowerService $borrowerService,
        protected DivisionService $divisionService
    ) {}

    public function index()
    {
        $borrowers = $this->borrowerService->getAllBorrowers();

        return Inertia::render('borrower/Index', [
            'borrowers' => BorrowerResource::collection($borrowers)->resolve(),
        ]);
    }

    public function create()
    {
        $divisions = $this->divisionService->getAllDivisions();

        return Inertia::render('borrower/Create', [
            'divisions' => DivisionResource::collection($divisions)->resolve()
        ]);
    }

    public function store(BorrowerRequest $request)
    {
        $this->borrowerService->createBorrower($request->validated());

        return redirect()->route('borrowers.index')->with('success', 'Borrower created successfully.');
    }

    public function show(int $id)
    {
        $borrower = $this->borrowerService->getBorrowerById($id);

        return Inertia::render('borrower/Show', [
            'borrower' => new BorrowerResource($borrower),
        ]);
    }

    public function edit(int $id)
    {
        $borrower = $this->borrowerService->getBorrowerById($id);

        return Inertia::render('borrower/Edit', [
            'borrower' => new BorrowerResource($borrower),
            'divisions' => $this->divisionService->getAllDivisions(),
        ]);
    }

    public function update(BorrowerRequest $request, int $id)
    {
        $borrower = $this->borrowerService->getBorrowerById($id);

        $this->borrowerService->updateBorrower($borrower, $request->validated());

        return redirect()->route('borrowers.index')->with('success', 'Borrower updated successfully.');
    }

    public function destroy(int $id)
    {
        $borrower = $this->borrowerService->getBorrowerById($id);

        $this->borrowerService->deleteBorrower($borrower);

        return redirect()->route('borrowers.index')->with('success', 'Borrower deleted successfully.');
    }
}
