<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardRecentActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type?->value,
            'description' => $this->description,
            'sample_id' => $this->sample_id,
            'sample_code' => $this->sample?->code,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'metadata' => $this->metadata ?? (object) [],
        ];
    }
}
