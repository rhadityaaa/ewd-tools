<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AspectVersionResource extends JsonResource
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
            'aspect_id' => $this->aspect_id,
            'version_number' => $this->version_number,
            'name' => $this->name,
            'description' => $this->description,
            'effective_from' => $this->effective_from,
            'questions' => QuestionVersionResource::collection($this->whenLoaded('questionVersions')),
        ];
    }
}
