<?php

namespace App\Services;

use App\Http\Resources\BorrowerResource;
use App\Models\Aspect;
use App\Models\Borrower;

class FormService
{
    public function getFormData()
    {
        $borrowers = Borrower::all();
        
        return [
            'borrowers' => BorrowerResource::collection($borrowers),
            'aspectGroups' => $this->getAspectGroupsForForm(),
            'borrower_id' => null
        ];
    }

    public function getAspectGroupsForForm()
    {
        $aspects = $this->getAspectsWithVersions();
        
        return $this->transformAspectData($aspects);
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
            'aspectVersions.questionVersions.questionOptions'
        ])->get();
    }

    private function transformAspectData($aspects)
    {
        $aspectGroups = $aspects->map(function($aspect) {
            $latestVersion = $aspect->aspectVersions->first();
            
            return [
                'id' => $aspect->code,
                'name' => $latestVersion ? $latestVersion->name : $aspect->name,
                'aspects' => $this->transformQuestions($latestVersion, $aspect)
            ];
        })->filter(function($group) {
            return count($group['aspects']) > 0;
        });

        return $aspectGroups->values()->toArray();
    }

    private function transformQuestions($latestVersion, $aspect)
    {
        if (!$latestVersion) {
            return [];
        }

        return $latestVersion->questionVersions->map(function($qv, $index) use ($aspect) {
            return [
                'id' => $aspect->code . '.' . ($index + 1),
                'question' => $qv->question_text,
                'value' => '',
                'notes' => '',
                'question_version_id' => $qv->id,
                'options' => $qv->questionOptions->map(function($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text, 
                        'score' => $option->score
                    ];
                })
            ];
        })->toArray();
    }
}
