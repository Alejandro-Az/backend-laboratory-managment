<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $baseData = [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status?->value,
            'priority' => $this->priority?->value,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->name,
            'client_id' => $this->project?->client_id,
            'client_name' => $this->project?->client?->name,
            'received_at' => $this->received_at?->format('Y-m-d'),
            'notes' => $this->notes,
            'rejection_count' => $this->rejection_count,
            'analysis_started_at' => $this->analysis_started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'latest_result_summary' => $this->whenLoaded('latestResult', function () {
                if (!$this->latestResult) {
                    return null;
                }
                return [
                    'result_summary' => $this->latestResult->result_summary,
                    'analyzed_at' => $this->latestResult->created_at?->toIso8601String(),
                    'analyst_name' => $this->latestResult->analyst?->name,
                ];
            }),
            'latest_result_at' => $this->latestResult?->created_at?->toIso8601String(),
            'results_count' => $this->results_count ?? 0,
            'created_by_name' => $this->creator?->name,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        if ($this->relationLoaded('results')) {
            $baseData['results'] = SampleResultResource::collection(
                $this->results->sortByDesc('created_at')
            );
        }

        if ($this->relationLoaded('latestResult') && $this->latestResult) {
            $baseData['latest_result'] = new SampleResultResource($this->latestResult);
        }

        return $baseData;
    }
}
