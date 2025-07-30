<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionItemResource extends JsonResource
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
            'monitoring_note_id' => $this->monitoring_note_id,
            'action' => $this->action_description,
            'item_type' => $this->item_type,
            'progress_notes' => $this->progress_notes ?? '',
            'people_in_charge' => $this->notes ?? '',
            'due_date' => $this->due_date,
            'completion_date' => $this->completion_date,
            'status' => $this->status,
        ];
    }
}
