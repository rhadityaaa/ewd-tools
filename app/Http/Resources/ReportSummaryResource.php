<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportSummaryResource extends JsonResource
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
            'report_id' => $this->report_id,
            'final_classification' => $this->final_classification,
            'indicative_collectibility' => $this->indicative_collectibility,
            'is_override' => $this->is_override,
            'override_reason' => $this->override_reason,
            'business_notes' => $this->business_notes,
            'reviewer_notes' => $this->reviewer_notes,
            'summary_generated_at' => $this->summary_generated_at,
            'report' => $this->whenLoaded('report', function () {
                return new ReportResource($this->report);
            })
        ];
    }
}
