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
            $template->update(['name' => $data['name']]);

            $latestVersion = $template->latestVersion;
            
            $needsNewVersion = (
                $latestVersion->description !== ($data['description'] ?? null) ||
                $this->aspectsChanged($latestVersion, $data['selected_aspects'] ?? [])
            );

            if ($needsNewVersion) {
                $templateVersion = $template->templateVersions()->create([
                    'version_number' => $latestVersion->version_number + 1,
                    'description' => $data['description'] ?? null,
                    'effective_from' => now(),
                ]);
            } else {
                $templateVersion = $latestVersion;
                $templateVersion->update([
                    'description' => $data['description'] ?? null,
                ]);
            }

            if (!empty($data['selected_aspects'])) {
                $pivotData = $this->prepareAspectPivotData($data['selected_aspects']);
                $templateVersion->aspectVersions()->sync($pivotData);
            } else {
                $templateVersion->aspectVersions()->detach();
            }

            $templateVersion->visibilityRules()->delete();
            if (!empty($data['visibility_rules'])) {
                $templateVersion->visibilityRules()->createMany($data['visibility_rules']);
            }

            return $template->load('latestVersion.aspectVersions');
        });
    }

    private function aspectsChanged($templateVersion, $newAspects): bool
    {
        $currentAspects = $templateVersion->aspectVersions->map(function($av) {
            return [
                'id' => $av->aspect_id,
                'weight' => $av->pivot->weight
            ];
        })->sortBy('id')->values()->toArray();
        
        $newAspectsFormatted = collect($newAspects)->sortBy('id')->values()->toArray();
        
        return $currentAspects !== $newAspectsFormatted;
    }

    public function deleteTemplate(Template $template): void
    {
        DB::transaction(function () use ($template) {
            foreach ($template->templateVersions as $version) {
                $version->aspectVersions()->detach();
                $version->visibilityRules()->delete();
                $version->delete();
            }
            
            $template->delete();
        });
    }

    private function prepareAspectPivotData(array $selectedAspects): Array
    {
        return collect($selectedAspects)->mapWithKeys(function ($selectedAspect) {
            $aspect = Aspect::find($selectedAspect['id']);
            $latestAspectVersion = $aspect->latestAspectVersion;

            if (!$latestAspectVersion) {
                throw new Exception("Aspek '{$aspect->name}' tidak memiliki versi yang bisa digunakan.");
            }

            return [$latestAspectVersion->id => ['weight' => $selectedAspect['weight']]];
        })->all();
    }
}