<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AspectResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->latestAspectVersion->name,
            'description' => $this->latestAspectVersion->description,
            'version_number' => $this->latestAspectVersion->version_number,
            'effective_from' => $this->latestAspectVersion->effective_from,
            'questions_count' => $this->latestAspectVersion->questionVersions->count(),
            'latest_version' => new AspectVersionResource($this->latestAspectVersion),
        ];
    }
}
