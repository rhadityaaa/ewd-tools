<?php

namespace App\Services;

use App\Http\Resources\BorrowerResource;
use App\Models\Aspect;
use App\Models\Borrower;
use App\Models\Template;
use Illuminate\Support\Facades\Log;

class FormService
{
    public function getFormData($templateId = null, $borrowerData = [], $facilityData = [])
    {
        $borrowers = Borrower::all();
        
        // Auto-select template if not provided but borrower/facility data exists
        if (!$templateId && (!empty($borrowerData) || !empty($facilityData))) {
            $templateId = $this->getApplicableTemplate($borrowerData, $facilityData);
        }
        
        return [
            'borrowers' => BorrowerResource::collection($borrowers),
            'aspectGroups' => $this->getAspectGroupsForForm($templateId),
            'borrower_id' => null,
            'template_id' => $templateId
        ];
    }

    public function getAspectGroupsForForm($templateId = null)
    {
        if ($templateId) {
            return $this->getAspectGroupsFromTemplate($templateId);
        }
        
        $aspects = $this->getAspectsWithVersions();
        return $this->transformAspectData($aspects);
    }

    private function getAspectGroupsFromTemplate($templateId)
    {
        $template = Template::with([
            'latestVersion.aspectVersions.questionVersions' => function($query) {
                $query->where('effective_from', '<=', now())
                    ->orderBy('version_number', 'desc');
            },
            'latestVersion.aspectVersions.questionVersions.questionOptions',
            'latestVersion.aspectVersions.questionVersions.visibilityRules',
            'latestVersion.aspectVersions.aspect',
            'latestVersion.visibilityRules'
        ])->findOrFail($templateId);

        $aspectGroups = [];
        
        foreach ($template->latestVersion->aspectVersions as $aspectVersion) {
            $aspectGroups[] = [
                'id' => $aspectVersion->aspect->code,
                'name' => $aspectVersion->name,
                'weight' => $aspectVersion->pivot->weight,
                'aspects' => $this->transformQuestionsFromTemplate($aspectVersion),
                'template_visibility_rules' => $template->latestVersion->visibilityRules->map(function($rule) {
                    return [
                        'id' => $rule->id,
                        'entity_id' => $rule->entity_id,
                        'entity_type' => $rule->entity_type,
                        'source_type' => $rule->source_type,
                        'source_field' => $rule->source_field,
                        'operator' => $rule->operator,
                        'value' => $rule->value,
                        'description' => $rule->description,
                    ];
                })->toArray()
            ];
        }

        return $aspectGroups;
    }

    private function transformQuestionsFromTemplate($aspectVersion)
    {
        return $aspectVersion->questionVersions->map(function($qv, $index) use ($aspectVersion) {
            return [
                'id' => $aspectVersion->aspect->code . '.' . ($index + 1),
                'question' => $qv->question_text,
                'value' => '',
                'notes' => '',
                'question_version_id' => $qv->id,
                'is_mandatory' => $qv->is_mandatory,
                'weight' => $qv->weight,
                'max_score' => $qv->max_score,
                'min_score' => $qv->min_score,
                'options' => $qv->questionOptions->map(function($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text, 
                        'score' => $option->score
                    ];
                }),
                'visibility_rules' => $qv->visibilityRules->map(function($rule) {
                    return [
                        'id' => $rule->id,
                        'entity_id' => $rule->entity_id,
                        'entity_type' => $rule->entity_type,
                        'source_type' => $rule->source_type,
                        'source_field' => $rule->source_field,
                        'operator' => $rule->operator,
                        'value' => $rule->value,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    private function getAspectsWithVersions()
    {
        return Aspect::with([
            'aspectVersions' => function($query) {
                $query->where('effective_from', '<=', now())
                    ->orderBy('version_number', 'desc')
                    ->limit(1);
            },
            'aspectVersions.questionVersions' => function($query) {
                $query->where('effective_from', '<=', now())
                    ->orderBy('version_number', 'desc');
            },
            'aspectVersions.questionVersions.questionOptions',
            'aspectVersions.questionVersions.visibilityRules'
        ])->get();
    }

    private function transformAspectData($aspects)
    {
        $aspectGroups = $aspects->map(function($aspect) {
            $latestVersion = $aspect->aspectVersions->first();
            
            return [
                'id' => $aspect->code,
                'name' => $latestVersion ? $latestVersion->name : $aspect->name,
                // Fix: Pass the questionVersions collection, not the aspectVersion model
                'aspects' => $latestVersion ? $this->transformQuestions($latestVersion->questionVersions, $aspect->code) : []
            ];
        })->filter(function($group) {
            return count($group['aspects']) > 0;
        });

        return $aspectGroups->values()->toArray();
    }

    private function transformQuestions($questionVersions, $aspectCode)
    {
        // Add null check to prevent errors when questionVersions is null
        if (!$questionVersions) {
            return [];
        }
        
        return $questionVersions->map(function($qv, $index) use ($aspectCode) {
            return [
                'id' => $aspectCode . '.' . ($index + 1),
                'question' => $qv->question_text,
                'value' => '',
                'notes' => '',
                'question_version_id' => $qv->id,
                'is_mandatory' => $qv->is_mandatory,
                'weight' => $qv->weight,
                'max_score' => $qv->max_score,
                'min_score' => $qv->min_score,
                'options' => $qv->questionOptions->map(function($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text, 
                        'score' => $option->score
                    ];
                }),
                'visibility_rules' => $qv->visibilityRules->map(function($rule) {
                    return [
                        'id' => $rule->id,
                        'entity_id' => $rule->entity_id,
                        'entity_type' => $rule->entity_type,
                        'source_type' => $rule->source_type,
                        'source_field' => $rule->source_field,
                        'operator' => $rule->operator,
                        'value' => $rule->value,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    // Remove the duplicate method at lines 181-186:
    // DELETE THIS DUPLICATE METHOD:
    // private function transformQuestionsFromTemplate($aspectVersion)
    // {
    //     return $this->transformQuestions(
    //         $aspectVersion->questionVersions, 
    //         $aspectVersion->aspect->code
    //     );
    // }

    public function checkTemplateVisibility($templateId, $borrowerData, $facilityData = [])
    {
        $template = Template::with('latestVersion.visibilityRules')->findOrFail($templateId);
        
        Log::info('Checking template visibility', [
            'template_id' => $templateId,
            'rules_count' => $template->latestVersion?->visibilityRules->count() ?? 0,
            'borrowerData' => $borrowerData,
            'facilityData' => $facilityData
        ]);
        
        if (!$template->latestVersion || !$template->latestVersion->visibilityRules->count()) {
            Log::info('No rules found - template visible');
            return true;
        }
    
        $result = $template->latestVersion->visibilityRules->every(function($rule) use ($borrowerData, $facilityData) {
            $ruleResult = $this->evaluateVisibilityRule($rule, $borrowerData, $facilityData);
            Log::info('Rule evaluation result', [
                'rule_id' => $rule->id,
                'result' => $ruleResult
            ]);
            return $ruleResult;
        });
        
        Log::info('Final template visibility result', ['visible' => $result]);
        return $result;
    }

    private function evaluateVisibilityRule($rule, $borrowerData, $facilityData)
    {
        Log::info('Evaluating visibility rule', [
            'rule_id' => $rule->id,
            'source_type' => $rule->source_type,
            'source_field' => $rule->source_field,
            'operator' => $rule->operator,
            'target_value' => $rule->value,
            'borrowerData' => $borrowerData,
            'facilityData' => $facilityData
        ]);
        
        $sourceValue = null;
        
        switch ($rule->source_type) {
            case 'borrower_detail':
                $sourceValue = $borrowerData[$rule->source_field] ?? null;
                Log::info('Borrower detail evaluation', [
                    'field' => $rule->source_field,
                    'source_value' => $sourceValue
                ]);
                break;
            case 'borrower_facility':
                Log::info('Evaluating borrower_facility rule', [
                    'field' => $rule->source_field,
                    'facility_count' => count($facilityData),
                    'facilities' => $facilityData
                ]);
                
                // Check if any facility matches the condition
                if (!empty($facilityData)) {
                    foreach ($facilityData as $index => $facility) {
                        $facilityValue = $facility[$rule->source_field] ?? null;
                        Log::info('Checking facility', [
                            'facility_index' => $index,
                            'facility_data' => $facility,
                            'field_value' => $facilityValue,
                            'target_value' => $rule->value,
                            'operator' => $rule->operator
                        ]);
                        
                        $comparisonResult = $this->compareValues($facilityValue, $rule->operator, $rule->value);
                        Log::info('Facility comparison result', [
                            'facility_index' => $index,
                            'comparison_result' => $comparisonResult
                        ]);
                        
                        if ($comparisonResult) {
                            Log::info('Facility rule matched - returning true');
                            return true;
                        }
                    }
                    Log::info('No facility matched - returning false');
                    return false;
                } else {
                    Log::info('No facility data available - returning false');
                    return false;
                }
                break;
            case 'answer':
                // This would need to be implemented based on previous answers
                $sourceValue = null;
                Log::info('Answer rule not implemented yet');
                break;
        }
    
        $result = $this->compareValues($sourceValue, $rule->operator, $rule->value);
        Log::info('Rule evaluation completed', [
            'source_value' => $sourceValue,
            'operator' => $rule->operator,
            'target_value' => $rule->value,
            'result' => $result
        ]);
        
        return $result;
    }

    private function compareValues($sourceValue, $operator, $targetValue)
    {
        Log::info('Comparing values', [
            'source_value' => $sourceValue,
            'source_type' => gettype($sourceValue),
            'operator' => $operator,
            'target_value' => $targetValue,
            'target_type' => gettype($targetValue)
        ]);
        
        $result = false;
        
        switch ($operator) {
            case '=':
                $result = $sourceValue == $targetValue;
                break;
            case '!=':
                $result = $sourceValue != $targetValue;
                break;
            case '>':
                $result = $sourceValue > $targetValue;
                break;
            case '<':
                $result = $sourceValue < $targetValue;
                break;
            case '>=':
                $result = $sourceValue >= $targetValue;
                break;
            case '<=':
                $result = $sourceValue <= $targetValue;
                break;
            case 'in':
                $values = explode(',', $targetValue);
                $result = in_array($sourceValue, array_map('trim', $values));
                break;
            case 'not_in':
                $values = explode(',', $targetValue);
                $result = !in_array($sourceValue, array_map('trim', $values));
                break;
            default:
                Log::warning('Unknown operator', ['operator' => $operator]);
                $result = false;
        }
        
        Log::info('Comparison result', ['result' => $result]);
        return $result;
    }

    public function getApplicableTemplate($borrowerData, $facilityData)
    {
        Log::info('Getting applicable template with data:', [
            'borrowerData' => $borrowerData,
            'facilityData' => $facilityData
        ]);
        
        $templates = Template::with('latestVersion.visibilityRules')->get();
        
        foreach ($templates as $template) {
            Log::info('Checking template:', [
                'template_id' => $template->id,
                'template_name' => $template->name,
                'rules_count' => $template->latestVersion?->visibilityRules->count() ?? 0
            ]);
            
            // PERBAIKAN: Gunakan $template->id bukan $template
            if ($this->checkTemplateVisibility($template->id, $borrowerData, $facilityData)) {
                Log::info('Template selected:', [
                    'template_id' => $template->id,
                    'template_name' => $template->name
                ]);
                return $template->id;
            }
        }
        
        Log::warning('No template matched, returning default template');
        // Return default template atau null
        $defaultTemplate = Template::first();
        return $defaultTemplate ? $defaultTemplate->id : null;
    }
}
