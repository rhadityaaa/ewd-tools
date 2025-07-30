<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'borrower_id' => $this->borrower_id,
            'template_id' => $this->template_id,
            'period_id' => $this->period_id,
            'status' => $this->status,
            'borrower' => $this->whenLoaded('borrower', function () {
                return new BorrowerResource($this->borrower);
            }),
            'template' => $this->whenLoaded('template', function () {
                return new TemplateResource($this->template);
            }),
            'period' => $this->whenLoaded('period', function () {
                return new PeriodResource($this->period);
            }),
            'summary' => $this->whenLoaded('summary', function () {
                return new ReportSummaryResource($this->summary);
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
