<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateRequest;
use App\Http\Resources\TemplateResource;
use App\Services\AspectBuilderService;
use App\Services\TemplateBuilderService;
use App\Services\TemplateService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateController extends Controller
{
    protected $templateService;
    protected $aspectService;

    public function __construct(
        TemplateBuilderService $templateService,
        AspectBuilderService $aspectService,
    )
    {
        $this->templateService = $templateService;
        $this->aspectService = $aspectService;
    }

    public function index()
    {
        $templates = $this->templateService->getAllTemplates();

        return Inertia::render('template/Index', [
            'templates' => TemplateResource::collection($templates)->resolve()
        ]);
    }

    public function create()
    {
        $aspects = $this->aspectService->getAllAspects();

        return Inertia::render('template/Create', [
            'aspects' => $aspects
        ]);
    }

    public function store(TemplateRequest $request)
    {
        $this->templateService->createTemplate($request->validated());

        return redirect()->route('templates.index')->with('success', 'Template created successfully.');
    }

    public function show(int $id)
    {
        $template = $this->templateService->getTemplateById($id);

        return Inertia::render('template/Show', [
            'template' => new TemplateResource($template),
        ]);
    }

    public function edit(int $id)
    {
        $template = $this->templateService->getTemplateById($id);

        return Inertia::render('template/Edit', [
            'template' => new TemplateResource($template),
        ]);
    }

    public function update(TemplateRequest $request, int $id)
    {
        $template = $this->templateService->getTemplateById($id);

        $this->templateService->updateTemplate($template, $request->validated());

        return redirect()->route('templates.index')->with('success', 'Template updated successfully.');
    }

    public function destroy(int $id)
    {
        $template = $this->templateService->getTemplateById($id);

        $this->templateService->deleteTemplate($template);

        return redirect()->route('templates.index')->with('success', 'Template deleted successfully.');
    }
}
