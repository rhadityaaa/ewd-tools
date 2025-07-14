<?php

namespace App\Services;

use App\Models\Aspect;
use App\Models\Template;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TemplateBuilderService
{
    public function getAllTemplates(): Collection
    {
        $templates = Template::all();

        return $templates;
    }

    public function getTemplateById(int $id): Template
    {
        $template = Template::findOrFail($id);

        return $template;
    }

    public function createTemplate(array $data): Template
    {
        return DB::transaction(function () use ($data) {
            $template = Template::create(['name' => $data['name']]);

            $templateVersion = $template->templateVersions()->create([
                'version_number' => 1,
                'description' => $data['description'] ?? null,
                'effective_from' => now(),
            ]);

            if (!empty($data['selected_aspects'])) {
                $pivotData = $this->prepareAspectPivotData($data['selected_aspects']);
                $templateVersion->aspectVersions()->sync($pivotData);
            }

            if (!empty($data['visibility_rules'])) {
                $templateVersion->visibilityRules()->createMany($data['visibility_rules']);
            }

            return $template->load('latestVersion.aspectVersions');
        });
    }

    public function updateTemplate(Template $template, array $data): Template
    {
        return DB::transaction(function () use ($template, $data) {
            if (isset($data['name'])) {
                $template->update(['name' => $data['name']]);
            }

            $latestVersion = $template->latestVersion;
            $newVersionNumber = $latestVersion ? $latestVersion->version_number + 1 : 1;

            $newTemplateVersion = $template->templateVersions()->create([
                'version_number' => $newVersionNumber,
                'description' => $data['description'] ?? null,
                'effective_from' => now(),
            ]);

            if (!empty($data['selected_aspects'])) {
                $pivotData = $this->prepareAspectPivotData($data['selected_aspects']);
                $newTemplateVersion->aspectVersions()->sync($pivotData);
            }

            if (!empty($data['visibility_rules'])) {
                $newTemplateVersion->visibilityRules()->createMany($data['visibility_rules']);
            }

            return $template->load('latestVersion.aspectVersions');
        });
    }

    public function deleteTemplate(Template $template): void
    {
        $template->delete();
    }

    private function prepareAspectPivotData(array $selectedAspects): Array
    {
        return collect($selectedAspects)->mapWithKeys(function ($selectedAspect) {
            $aspect = Aspect::find($selectedAspect['id']);
            $latestAspectVersion = $aspect->latestVersion;

            if (!$latestAspectVersion) {
                throw new Exception("Aspek '{$aspect->name}' tidak memiliki versi yang bisa digunakan.");
            }

            return [$latestAspectVersion->id => ['weight' => $selectedAspect['weight']]];
        })->all();
    }
}