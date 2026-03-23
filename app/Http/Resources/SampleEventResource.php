<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type?->value,
            'description' => $this->description,
            'old_status' => $this->old_status?->value,
            'new_status' => $this->new_status?->value,
            'old_priority' => $this->old_priority?->value,
            'new_priority' => $this->new_priority?->value,
            'metadata' => $this->metadata,
            'user_name' => $this->user?->name,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
