<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SamplePriority;
use App\Enums\SampleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DashboardMetricsResource;
use App\Http\Resources\DashboardRecentActivityResource;
use App\Http\Resources\DashboardRecentSampleResource;
use App\Models\Sample;
use App\Models\SampleEvent;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function metrics(): JsonResponse
    {
        $totalSamples = Sample::query()->count();
        $urgentSamples = Sample::query()
            ->where('priority', SamplePriority::URGENT->value)
            ->count();
        $pendingAnalysis = Sample::query()
            ->whereIn('status', [
                SampleStatus::PENDING->value,
                SampleStatus::IN_PROGRESS->value,
            ])
            ->count();
        $completedSamples = Sample::query()
            ->where('status', SampleStatus::COMPLETED->value)
            ->count();

        $completionRate = $totalSamples > 0
            ? (int) round(($completedSamples / $totalSamples) * 100)
            : 0;

        $samplesWithRejections = Sample::query()
            ->where('rejection_count', '>', 0)
            ->count();

        $rejectionRate = $totalSamples > 0
            ? (int) round(($samplesWithRejections / $totalSamples) * 100)
            : 0;

        return ApiResponse::success(
            new DashboardMetricsResource([
                'total_samples' => $totalSamples,
                'urgent_samples' => $urgentSamples,
                'pending_analysis' => $pendingAnalysis,
                'completion_rate' => $completionRate,
                'rejection_rate' => $rejectionRate,
            ]),
            'Dashboard metrics retrieved successfully.'
        );
    }

    public function recentSamples(): JsonResponse
    {
        $limit = min(max((int) request()->integer('limit', 5), 1), 50);

        $samples = Sample::query()
            ->whereNull('samples.deleted_at')
            ->with(['project.client', 'latestResult'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return ApiResponse::success([
            'items' => DashboardRecentSampleResource::collection($samples),
            'meta' => [
                'count' => $samples->count(),
            ],
        ], 'Recent samples retrieved successfully.');
    }

    public function recentActivity(): JsonResponse
    {
        $limit = min(max((int) request()->integer('limit', 10), 1), 100);

        $events = SampleEvent::query()
            ->whereHas('sample')
            ->with(['sample', 'user'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return ApiResponse::success([
            'items' => DashboardRecentActivityResource::collection($events),
            'meta' => [
                'count' => $events->count(),
            ],
        ], 'Recent activity retrieved successfully.');
    }
}
