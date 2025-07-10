<?php

namespace App\Http\Controllers;

use App\Http\Requests\AspectBuilderRequest;
use App\Http\Resources\AspectResource;
use App\Models\Aspect;
use App\Services\AspectBuilderService;
use Exception;
use Inertia\Inertia;

class AspectController extends Controller
{
    public function __construct(
        protected AspectBuilderService $aspectBuilderService
    ) {}

    public function index()
    {
        $aspects = Aspect::with('latestAspectVersion')->latest()->get();

        return Inertia::render('aspect/Index', [
            'aspects' => AspectResource::collection($aspects)
        ]);
    }

    public function create()
    {
        return Inertia::render('aspect/Create');
    }

    public function store(AspectBuilderRequest $request)
    {
        try {
            $validated = $request->validated();

            $this->aspectBuilderService->createAspectWithQuestions($validated);

            return redirect()->route('aspects.index')->with('success', 'Aspect created successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to create aspect: ' . $e->getMessage()]);
        }
    }

    public function show(Aspect $aspect)
    {
        $aspect->load(['aspectVersions.questionVersions.questionOptions', 'aspectVersions.questionVersions.visibilityRules']);

        return Inertia::render('aspect/Show', [
            'aspect' => new AspectResource($aspect),
        ]);
    }

    public function edit(Aspect $aspect)
    {
        $aspect->load(['aspectVersions.questionVersions.questionOptions', 'aspectVersions.questionVersions.visibilityRules']);

        return Inertia::render('aspect/Edit', [
            'aspect' => new AspectResource($aspect),
        ]);
    }

    public function update(AspectBuilderRequest $request, Aspect $aspect)
    {
        try {
            $validated = $request->validated();

            $this->aspectBuilderService->updateAspectWithQuestions($aspect, $validated);

            return redirect()->route('aspects.index')->with('success', 'Aspect updated successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to update aspect: ' . $e->getMessage()]);
        }
    }

    public function destroy(Aspect $aspect)
    {
        try {
            $aspect->delete();

            return redirect()->route('aspects.index')->with('success', 'Aspect deleted successfully.');
        } catch (Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete aspect: ' . $e->getMessage()]);
        }
    }
}
