<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
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
            'status' => $this->status,
            'status_label' => $this
        ];
    }

    private function getStatusLabel(): string
    {
        $labels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];

        return $labels[$this->status] ?? ucfirst($this->status);
    }

    private function calculateTotalQuestions(): int
    {
        return $this->aspects->sum(function ($aspect) {
            return $aspect->aspectVersion->questionsVersions->count();
        });
    }

    private function calculateCompletionPercentage(): float
    {
        $totalQuestions = $this->calculateTotalQuestions();
        $answeredQuestions = $this->answers->count();

        return $totalQuestions > 0 ? ($answeredQuestions / $totalQuestions) * 100 : 0;
    }
}
