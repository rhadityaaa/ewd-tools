<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_id' => $this->role_id,
            'division_id' => $this->division_id,
            'role' => $this->whenLoaded('role'),
            'division' => $this->whenLoaded('division'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
