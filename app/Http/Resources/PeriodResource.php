<?php

namespace App\Http\Resources;

use App\Services\PeriodService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodResource extends JsonResource
{
    protected PeriodService $periodService;

    public function __construct($resource)
    {
        parent::__construct($resource);
        $this->periodService = app(PeriodService::class);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_date' => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->end_date?->format('Y-m-d H:i:s'),
            'status' => $this->status->value,
            'created_by' => $this->created_by,
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            'is_expired' => $this->periodService->isExpired($this->resource),
            'is_active' => $this->periodService->isActive($this->resource),
            'remaining_time' => $this->periodService->getRemainingTime($this->resource),
        ];
    }
}
