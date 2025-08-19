<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WatchlistResource extends JsonResource
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
            'report_id' => $this->report_id,
            'status' => $this->status,
            'added_by' => $this->added_by,
            'resolved_by' => $this->resolved_by,
            'resolver_notes' => $this->resolver_notes,
            'resolved_at' => $this->resolved_at?->format('Y-m-d H:i:s'),
            'borrower' => $this->whenLoaded('borrower', function () {
                return new BorrowerResource($this->borrower);
            }),
            'report' => $this->whenLoaded('report', function () {
                return new ReportResource($this->report);
            }),
            'addedBy' => $this->whenLoaded('addedBy', function () {
                return new UserResource($this->addedBy);
            }),
            'resolvedBy' => $this->whenLoaded('resolvedBy', function () {
                return new UserResource($this->resolvedBy);
            }),
            'monitoringNotes' => $this->whenLoaded('monitoringNotes', function () {
                return MonitoringNoteResource::collection($this->monitoringNotes);
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}