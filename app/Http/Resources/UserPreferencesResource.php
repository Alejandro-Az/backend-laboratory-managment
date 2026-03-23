<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferencesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'notify_urgent_sample_alerts' => $this->notify_urgent_sample_alerts,
            'notify_sample_completion' => $this->notify_sample_completion,
            'notify_daily_activity_digest' => $this->notify_daily_activity_digest,
            'notify_project_updates' => $this->notify_project_updates,
        ];
    }
}
