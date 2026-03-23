<?php

namespace App\Models;

use App\Enums\SampleEventType;
use App\Enums\SamplePriority;
use App\Enums\SampleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'sample_id',
        'user_id',
        'event_type',
        'description',
        'old_status',
        'new_status',
        'old_priority',
        'new_priority',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => SampleEventType::class,
            'old_status' => SampleStatus::class,
            'new_status' => SampleStatus::class,
            'old_priority' => SamplePriority::class,
            'new_priority' => SamplePriority::class,
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
