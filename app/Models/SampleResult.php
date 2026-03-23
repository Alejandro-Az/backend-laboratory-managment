<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'sample_id',
        'analyst_id',
        'result_summary',
        'result_data',
    ];

    protected function casts(): array
    {
        return [
            'result_data' => 'array',
        ];
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }
}
