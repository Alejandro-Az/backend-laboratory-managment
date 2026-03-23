<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardMetricsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_samples' => $this->resource['total_samples'],
            'urgent_samples' => $this->resource['urgent_samples'],
            'pending_analysis' => $this->resource['pending_analysis'],
            'completion_rate' => $this->resource['completion_rate'],
            'rejection_rate' => $this->resource['rejection_rate'],
        ];
    }
}
