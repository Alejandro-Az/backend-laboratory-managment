<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreClientRequest;
use App\Http\Requests\Api\V1\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);

        $perPage = (int) $request->integer('per_page', 15);
        $search = $request->string('search')->toString();

        $query = Client::query()
            ->when($search, fn ($builder) => $builder->where('name', 'like', '%'.$search.'%'))
            ->orderByDesc('id');

        $clients = $query->paginate(min(max($perPage, 1), 100))->withQueryString();

        return ApiResponse::success([
            'items' => ClientResource::collection($clients->items()),
            'meta' => [
                'current_page' => $clients->currentPage(),
                'last_page' => $clients->lastPage(),
                'per_page' => $clients->perPage(),
                'total' => $clients->total(),
            ],
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $client = Client::create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(new ClientResource($client), 'Client created successfully.', 201);
    }

    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return ApiResponse::success(new ClientResource($client));
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return ApiResponse::success(new ClientResource($client->refresh()), 'Client updated successfully.');
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return ApiResponse::success((object) [], 'Client deleted successfully.');
    }
}
