<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\SampleEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreSampleRequest;
use App\Http\Requests\Api\V1\UpdateSampleRequest;
use App\Http\Requests\Api\V1\UpdateSampleStatusRequest;
use App\Http\Requests\Api\V1\UpdateSamplePriorityRequest;
use App\Http\Requests\Api\V1\StoreSampleResultRequest;
use App\Http\Resources\SampleDetailResource;
use App\Http\Resources\SampleEventResource;
use App\Http\Resources\SampleListResource;
use App\Models\Sample;
use App\Models\SampleEvent;
use App\Models\SampleResult;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SampleController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Sample::class);

        $perPage = (int) request()->integer('per_page', 15);

        $samples = Sample::query()
            ->with(['project.client', 'latestResult.analyst', 'creator'])
            ->withCount('results')
            ->byStatus(request('status'))
            ->byPriority(request('priority'))
            ->byClient(request('client_id'))
            ->byProject(request('project_id'))
            ->receivedFrom(request('received_from'))
            ->receivedTo(request('received_to'))
            ->orderByDesc('created_at')
            ->paginate(min(max($perPage, 1), 100))
            ->withQueryString();

        return ApiResponse::success([
            'items' => SampleListResource::collection($samples->items()),
            'meta' => [
                'current_page' => $samples->currentPage(),
                'last_page' => $samples->lastPage(),
                'per_page' => $samples->perPage(),
                'total' => $samples->total(),
            ],
        ]);
    }

    public function show(Sample $sample): JsonResponse
    {
        if ($sample->trashed()) {
            abort(404);
        }

        $this->authorize('view', $sample);

        $sample->load([
            'project.client',
            'results' => fn ($query) => $query->orderByDesc('created_at'),
            'latestResult.analyst',
            'creator',
            'updater',
        ]);

        return ApiResponse::success(new SampleDetailResource($sample));
    }

    public function store(StoreSampleRequest $request): JsonResponse
    {
        $this->authorize('create', Sample::class);

        $sample = Sample::create([
            'project_id' => $request->project_id,
            'code' => $request->code,
            'priority' => $request->priority,
            'status' => 'pending',
            'received_at' => $request->received_at,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        $this->createEvent($sample, SampleEventType::CREATED->value);

        $sample->load(['project.client', 'latestResult.analyst', 'creator']);

        return ApiResponse::success(new SampleDetailResource($sample), 'Sample created successfully.', 201);
    }

    public function update(Sample $sample, UpdateSampleRequest $request): JsonResponse
    {
        $this->authorize('update', $sample);

        $sample->update([
            'notes' => $request->notes,
            'updated_by' => auth()->id(),
        ]);

        $this->createEvent($sample, SampleEventType::UPDATED->value);

        $sample->load([
            'project.client',
            'results' => fn ($query) => $query->orderByDesc('created_at'),
            'latestResult.analyst',
            'creator',
            'updater',
        ]);

        return ApiResponse::success(new SampleDetailResource($sample), 'Sample updated successfully.');
    }

    public function updateStatus(Sample $sample, UpdateSampleStatusRequest $request): JsonResponse
    {
        $this->authorize('changeStatus', $sample);

        $oldStatus = $sample->status->value;
        $newStatus = $request->status;

        $sample->update([
            'status' => $newStatus,
            'updated_by' => auth()->id(),
        ]);

        if ($newStatus === 'in_progress' && $oldStatus === 'pending') {
            $sample->update(['analysis_started_at' => now()]);
            $this->createEvent($sample, SampleEventType::ANALYSIS_STARTED->value);
        }

        if ($newStatus === 'pending' && $oldStatus === 'in_progress') {
            $sample->increment('rejection_count');
        }

        if ($newStatus === 'completed') {
            $sample->update(['completed_at' => now()]);
            $this->createEvent($sample, SampleEventType::COMPLETED->value);
        }

        $this->createEvent(
            $sample,
            SampleEventType::STATUS_CHANGED->value,
            $oldStatus,
            $newStatus
        );

        $sample->load([
            'project.client',
            'results' => fn ($query) => $query->orderByDesc('created_at'),
            'latestResult.analyst',
            'creator',
            'updater',
        ]);

        return ApiResponse::success(new SampleDetailResource($sample), 'Sample status updated successfully.');
    }

    public function updatePriority(Sample $sample, UpdateSamplePriorityRequest $request): JsonResponse
    {
        $this->authorize('changePriority', $sample);

        $oldPriority = $sample->priority->value;
        $newPriority = $request->priority;

        $sample->update([
            'priority' => $newPriority,
            'updated_by' => auth()->id(),
        ]);

        $this->createEvent(
            $sample,
            SampleEventType::PRIORITY_CHANGED->value,
            $oldPriority,
            $newPriority
        );

        $sample->load([
            'project.client',
            'results' => fn ($query) => $query->orderByDesc('created_at'),
            'latestResult.analyst',
            'creator',
            'updater',
        ]);

        return ApiResponse::success(new SampleDetailResource($sample), 'Sample priority updated successfully.');
    }

    public function addResult(Sample $sample, StoreSampleResultRequest $request): JsonResponse
    {
        $this->authorize('addResult', $sample);

        $result = SampleResult::create([
            'sample_id' => $sample->id,
            'analyst_id' => auth()->id(),
            'result_summary' => $request->result_summary,
            'result_data' => $request->result_data,
        ]);

        $this->createEvent(
            $sample,
            SampleEventType::RESULT_ADDED->value,
            null,
            null,
            [
                'result_id' => $result->id,
                'analyst_name' => auth()->user()->name,
            ]
        );

        $sample->load([
            'project.client',
            'results' => fn ($query) => $query->orderByDesc('created_at'),
            'latestResult.analyst',
            'creator',
            'updater',
        ]);

        return ApiResponse::success(new SampleDetailResource($sample), 'Sample result added successfully.');
    }

    public function destroy(Sample $sample): JsonResponse
    {
        $this->authorize('delete', $sample);

        $sample->delete();

        $this->createEvent($sample, SampleEventType::DELETED->value);

        return ApiResponse::success((object) [], 'Sample deleted successfully.');
    }

    public function restore($id): JsonResponse
    {
        $sample = Sample::withTrashed()->findOrFail($id);

        $this->authorize('restore', $sample);

        $sample->restore();

        $this->createEvent($sample, SampleEventType::RESTORED->value);

        $sample->load([
            'project.client',
            'results' => fn ($query) => $query->orderByDesc('created_at'),
            'latestResult.analyst',
            'creator',
            'updater',
        ]);

        return ApiResponse::success(new SampleDetailResource($sample), 'Sample restored successfully.');
    }

    public function getEvents(Sample $sample): JsonResponse
    {
        $this->authorize('viewEvents', $sample);

        $perPage = (int) request()->integer('per_page', 20);

        $events = $sample->events()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(min(max($perPage, 1), 100))
            ->withQueryString();

        return ApiResponse::success([
            'items' => SampleEventResource::collection($events->items()),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    private function createEvent(
        Sample $sample,
        string $eventType,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?array $metadata = null
    ): void {
        $description = $this->generateEventDescription($eventType, $oldValue, $newValue);

        $data = [
            'sample_id' => $sample->id,
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'description' => $description,
            'metadata' => $metadata,
        ];

        if ($eventType === SampleEventType::STATUS_CHANGED->value) {
            $data['old_status'] = $oldValue;
            $data['new_status'] = $newValue;
        } elseif ($eventType === SampleEventType::PRIORITY_CHANGED->value) {
            $data['old_priority'] = $oldValue;
            $data['new_priority'] = $newValue;
        }

        SampleEvent::create($data);
    }

    private function generateEventDescription(string $eventType, ?string $oldValue = null, ?string $newValue = null): string
    {
        return match ($eventType) {
            SampleEventType::CREATED->value => 'Sample created',
            SampleEventType::UPDATED->value => 'Sample updated',
            SampleEventType::STATUS_CHANGED->value => "Status changed from {$oldValue} to {$newValue}",
            SampleEventType::PRIORITY_CHANGED->value => "Priority changed from {$oldValue} to {$newValue}",
            SampleEventType::ANALYSIS_STARTED->value => 'Analysis started',
            SampleEventType::COMPLETED->value => 'Sample completed',
            SampleEventType::RESULT_ADDED->value => 'Result added',
            SampleEventType::DELETED->value => 'Sample deleted',
            SampleEventType::RESTORED->value => 'Sample restored',
            default => 'Event',
        };
    }
}
