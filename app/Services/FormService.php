<?php

namespace App\Services;

use App\Models\AspectVersion;
use App\Models\Borrower;
use App\Models\QuestionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\VisibilityRule;
use Illuminate\Support\Facades\Log;

class FormService
{
    public function getFormData(?int $templateId = null, array $borrowerData = [], array $facilityData = [])
    {
        Log::info('FormService getFormData called', [
            'templateId' => $templateId,
            'borrowerData' => $borrowerData,
            'facilityData' => $facilityData
        ]);
        
        // Selalu evaluasi ulang template berdasarkan data terbaru
        // Jangan gunakan templateId yang diberikan jika ada data borrower dan facility
        $evaluatedTemplateId = null;
        if (!empty($borrowerData) && !empty($facilityData)) {
            $evaluatedTemplateId = $this->getApplicableTemplate($borrowerData, $facilityData);
            Log::info('Re-evaluated template', [
                'original_templateId' => $templateId,
                'evaluated_templateId' => $evaluatedTemplateId
            ]);
        }
        
        // Gunakan template yang dievaluasi, fallback ke templateId jika tidak ada
        $finalTemplateId = $evaluatedTemplateId ?? $templateId;
        
        $data = [
            'template_id' => $finalTemplateId,
            'borrower_data' => $borrowerData,
            'facility_data' => $facilityData,
            'aspectGroups' => [],
        ];
    
        if ($finalTemplateId) {
            $data['aspectGroups'] = $this->getAspectGroupsForForm($finalTemplateId, $borrowerData, $facilityData);
            Log::info('Aspect groups retrieved', [
                'template_id' => $finalTemplateId,
                'count' => count($data['aspectGroups'])
            ]);
        }

        return $data;
    }

    public function getApplicableTemplate(array $borrowerData, array $facilityData): ?int
    {
        Log::info('Getting applicable template', [
            'borrowerData' => $borrowerData,
            'facilityData' => $facilityData
        ]);
        
        $templates = Template::with(['latestVersion.visibilityRules'])->get();
        Log::info('Found templates', ['count' => $templates->count()]);
    
        foreach ($templates as $template) {
            $templateVersion = $template->latestVersion;
    
            if (!$templateVersion) {
                continue;
            }
    
            Log::info('Checking template', [
                'template_id' => $template->id,
                'template_name' => $template->name
            ]);
    
            if ($this->checkVisibility($templateVersion, $borrowerData, $facilityData)) {
                Log::info('Template matched!', ['template_id' => $template->id]);
                return $template->id;
            }
        }
    
        Log::warning('No template matched');
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
                'aspects' => $questions,  // Ubah dari 'questions' ke 'aspects'
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
                'question' => $questionVersion->question_text,  // Ubah dari 'text' ke 'question_text' sesuai model
                'weight' => $questionVersion->weight,
                'max_score' => $questionVersion->max_score,
                'min_score' => $questionVersion->min_score,
                'is_mandatory' => $questionVersion->is_mandatory,
                'options' => $questionVersion->questionOptions->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,  // Sesuai dengan field di model
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

            // dd($sourceValue); <- HAPUS BARIS INI
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