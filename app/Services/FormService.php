<?php

namespace App\Services;

use App\Models\AspectVersion;
use App\Models\Borrower;
use App\Models\QuestionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\VisibilityRule;

class FormService
{
    public function getFormData(?int $templateId = null, array $borrowerData = [], array $facilityData = [])
    {
        if (!$templateId && !empty($borrowerData) && !empty($facilityData)) {
            $templateId = $this->getApplicableTemplate($borrowerData, $facilityData);
        }
        
        $data = [
            'template_id' => $templateId,
            'borrower_data' => $borrowerData,
            'facility_data' => $facilityData,
            'aspectGroups' => [],
        ];

        if ($templateId) {
            $data['aspectGroups'] = $this->getAspectGroupsForForm($templateId, $borrowerData, $facilityData);
        }

        return $data;
    }

    public function getApplicableTemplate(array $borrowerData, array $facilityData): ?int
    {
        $templates = Template::with(['latestVersion.visibilityRules'])->get();

        foreach ($templates as $template) {
            $templateVersion = $template->latestVersion;

            if (!$templateVersion) {
                continue;
            }

            if ($this->checkVisibility($templateVersion, $borrowerData, $facilityData)) {
                return $template->id;
            }
        }

        return null;
    }

    public function getAspectGroupsForForm(int $templateId, array $borrowerData = [], array $facilityData = []): array
    {
        $template = Template::with([
            'latestVersion.aspectVersions.questionVersions.questionOptions',
            'latestVersion.aspectVersions.questionVersions.visibilityRules',
            'latestVersion.aspectVersions.visibilityRules'
        ])->find($templateId);

        if (!$template || !$template->latestVersion) {
            return [];
        }

        return $this->getAspectGroupsFromTemplate($template->latestVersion, $borrowerData, $facilityData);
    }

    private function getAspectGroupsFromTemplate(TemplateVersion $templateVersion, array $borrowerData = [], array $facilityData = [])
    {
        $aspectGroups = [];

        foreach ($templateVersion->aspectVersions as $aspectVersion) {
            $questions = $this->getVisibleQuestions($aspectVersion, $borrowerData, $facilityData);

            if (empty($questions)) {
                continue;
            }

            $aspectGroups[] = [
                'id' => $aspectVersion->id,
                'aspect_id' => $aspectVersion->aspect_id,
                'name' => $aspectVersion->name,
                'description' => $aspectVersion->description,
                'weight' => $aspectVersion->pivot->weight ?? 0,
                'questions' => $questions,
            ];
        }
        
        return $aspectGroups;
    }

    private function getVisibleQuestions(AspectVersion $aspectVersion, array $borrowerData = [], array $facilityData = [])
    {
        $questions = [];

        foreach ($aspectVersion->questionVersions as $questionVersion) {
            if (!$this->checkQuestionVisibility($questionVersion, $borrowerData, $facilityData)) {
                continue;
            }

            $questions[] = [
                'id' => $questionVersion->id,
                'question_id' => $questionVersion->question_id,
                'text' => $questionVersion->text,
                'type' => $questionVersion->type,
                'weight' => $questionVersion->weight,
                'options' => $questionVersion->questionOptions->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'text' => $option->text,
                        'value' => $option->value,
                        'score' => $option->score
                    ];
                })->toArray(),
                'visibility_rules' => $questionVersion->visibilityRules
            ];
        }

        return $questions;
    }

    private function checkQuestionVisibility(QuestionVersion $questionVersion, array $borrowerData, array $facilityData): bool
    {
        $visibilityRules = $questionVersion->visibilityRules;

        if ($visibilityRules->isEmpty()) {
            return true;
        }

        return $this->evaluateVisibilityRule($visibilityRules, $borrowerData, $facilityData);
    }

    public function checkVisibility($entity, array $borrowerData, array $facilityData): bool
    {
        $visibilityRules = $entity->visibilityRules ?? collect();

        if ($visibilityRules->isEmpty()) {
            return true;
        }

        return $this->evaluateVisibilityRule($visibilityRules, $borrowerData, $facilityData);
    }

    private function evaluateVisibilityRule($visibilityRules, array $borrowerData, array $facilityData): bool
    {
        foreach ($visibilityRules as $rule) {
            $sourceValue = null;
            switch ($rule->source_type) {
                case 'borrower_detail':
                    $sourceValue = data_get($borrowerData, $rule->source_field);
                    break;
                case 'borrower_facility':
                    if (isset($facilityData[0]) && is_array($facilityData[0])) {
                        $sourceValue = collect($facilityData)->sum(function ($facility) use ($rule) {
                            return $this->extractFacilityValue($rule->source_field, $facility);
                        });
                    } else {
                        $sourceValue = $this->extractFacilityValue($rule->source_field, $facilityData);
                    }
                    break;
            }

            if (!$this->compareValues($sourceValue, $rule->operator, $rule->value)) {
                return false;
            }
        }

        return true;
    }


   private function extractFacilityValue(string $field, array $facilityData)
    {
        if (str_starts_with($field, 'total_') || str_starts_with($field, 'sum_')) {
            $actualField = str_starts_with($field, 'total_') ? substr($field, 6) : substr($field, 4);
            return $facilityData[$actualField] ?? 0;
        }
        
        if (str_starts_with($field, 'avg_')) {
            $actualField = substr($field, 4);
            return $facilityData[$actualField] ?? 0;
        }
        
        if (str_starts_with($field, 'max_') || str_starts_with($field, 'min_')) {
            $actualField = str_starts_with($field, 'max_') ? substr($field, 4) : substr($field, 4);
            return $facilityData[$actualField] ?? 0;
        }
        
        if (str_starts_with($field, 'count_')) {
            $actualField = substr($field, 6);
            $value = $facilityData[$actualField] ?? null;
            return !empty($value) ? 1 : 0;
        }

        if (str_contains($field, '.')) {
            return data_get($facilityData, $field);
        }
        
        return $facilityData[$field] ?? null;
    }


    private function compareValues($sourceValue, string $operator, $targetValue): bool
    {
        if ($sourceValue === null) {
            return $operator === '!=' ? $targetValue !== null : false;
        }

        switch ($operator) {
            case '=':
                return $sourceValue == $targetValue;
            
            case '!=':
                return $sourceValue != $targetValue;
            
            case '>':
                return is_numeric($sourceValue) && is_numeric($targetValue) && $sourceValue > $targetValue;
            
            case '<':
                return is_numeric($sourceValue) && is_numeric($targetValue) && $sourceValue < $targetValue;
            
            case '>=':
                return is_numeric($sourceValue) && is_numeric($targetValue) && $sourceValue >= $targetValue;
            
            case '<=':
                return is_numeric($sourceValue) && is_numeric($targetValue) && $sourceValue <= $targetValue;
            
            case 'in':
                $targetArray = is_array($targetValue) ? $targetValue : explode(',', $targetValue);
                return in_array($sourceValue, array_map('trim', $targetArray));
            
            case 'not_in':
                $targetArray = is_array($targetValue) ? $targetValue : explode(',', $targetValue);
                return !in_array($sourceValue, array_map('trim', $targetArray));
            
            case 'contains':
                return is_string($sourceValue) && is_string($targetValue) && 
                       strpos(strtolower($sourceValue), strtolower($targetValue)) !== false;
            
            case 'not_contains':
                return is_string($sourceValue) && is_string($targetValue) && 
                       strpos(strtolower($sourceValue), strtolower($targetValue)) === false;
            
            default:
                return false;
        }
    }
}