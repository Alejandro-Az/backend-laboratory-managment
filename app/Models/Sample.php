<?php

namespace App\Models;

use App\Enums\SamplePriority;
use App\Enums\SampleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sample extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'code',
        'status',
        'priority',
        'received_at',
        'analysis_started_at',
        'completed_at',
        'notes',
        'rejection_count',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'priority' => SamplePriority::class,
            'received_at' => 'date',
            'analysis_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(SampleResult::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(SampleEvent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(SampleResult::class)->latestOfMany();
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn (Builder $builder) => $builder->where('status', $status));
    }

    public function scopeByPriority(Builder $query, ?string $priority): Builder
    {
        return $query->when($priority, fn (Builder $builder) => $builder->where('priority', $priority));
    }

    public function scopeByProject(Builder $query, ?int $projectId): Builder
    {
        return $query->when($projectId, fn (Builder $builder) => $builder->where('project_id', $projectId));
    }

    public function scopeByClient(Builder $query, ?int $clientId): Builder
    {
        return $query->when($clientId, fn (Builder $builder) => $builder->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('client_id', $clientId)));
    }

    public function scopeReceivedFrom(Builder $query, ?string $from): Builder
    {
        return $query->when($from, fn (Builder $builder) => $builder->whereDate('received_at', '>=', $from));
    }

    public function scopeReceivedTo(Builder $query, ?string $to): Builder
    {
        return $query->when($to, fn (Builder $builder) => $builder->whereDate('received_at', '<=', $to));
    }
}
