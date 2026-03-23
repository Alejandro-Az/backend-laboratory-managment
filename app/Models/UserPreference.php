<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_urgent_sample_alerts',
        'notify_sample_completion',
        'notify_daily_activity_digest',
        'notify_project_updates',
    ];

    protected function casts(): array
    {
        return [
            'notify_urgent_sample_alerts' => 'boolean',
            'notify_sample_completion' => 'boolean',
            'notify_daily_activity_digest' => 'boolean',
            'notify_project_updates' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
