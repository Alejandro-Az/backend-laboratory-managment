<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProjectRequest;
use App\Http\Requests\Api\V1\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $perPage = (int) $request->integer('per_page', 15);
        $status = $request->string('status')->toString();
        $clientId = $request->integer('client_id');
        $search = $request->string('search')->toString();

        $projects = Project::query()
            ->when($status, fn ($builder) => $builder->where('status', $status))
            ->when($clientId, fn ($builder) => $builder->where('client_id', $clientId))
            ->when($search, fn ($builder) => $builder->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id')
            ->paginate(min(max($perPage, 1), 100))
            ->withQueryString();

        return ApiResponse::success([
            'items' => ProjectResource::collection($projects->items()),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = Project::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(new ProjectResource($project), 'Project created successfully.', 201);
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ApiResponse::success(new ProjectResource($project));
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(new ProjectResource($project->refresh()), 'Project updated successfully.');
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return ApiResponse::success((object) [], 'Project deleted successfully.');
    }
}
