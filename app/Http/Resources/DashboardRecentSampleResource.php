<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardRecentSampleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'received_at' => $this->received_at?->format('Y-m-d'),
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'client_id' => $this->project?->client_id,
            'client_name' => $this->project?->client?->name,
            'latest_result_summary' => $this->latestResult?->result_summary,
            'latest_result_at' => $this->latestResult?->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
