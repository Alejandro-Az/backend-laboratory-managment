<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'result_summary' => $this->result_summary,
            'result_data' => $this->result_data,
            'analyzed_at' => $this->created_at->toIso8601String(),
            'analyst_name' => $this->analyst?->name,
        ];
    }
}
