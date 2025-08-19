<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionVersionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'aspect_version_id' => $this->aspect_version_id,
            'version_number' => $this->version_number,
            'question_text' => $this->question_text,
            'weight' => $this->weight,
            'max_score' => (float) $this->max_score,
            'min_score' => (float) $this->min_score,
            'is_mandatory' => (bool) $this->is_mandatory,
            'effective_from' => $this->effective_from,
            'options' => QuestionOptionResource::collection($this->questionOptions),
            'visibility_rules' => VisibilityRuleResource::collection($this->visibilityRules),
        ];
    }
}
