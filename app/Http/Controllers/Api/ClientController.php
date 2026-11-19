<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Queries\ClientQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(config('crm.client_statuses')))],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $clients = ClientQuery::build($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return ApiResponse::paginated(ClientResource::collection($clients));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $data = $request->validated();
        unset($data['logo'], $data['remove_logo']);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('clients', 'uploads');
        }

        $data['created_by'] = $request->user()->id;

        $client = DB::transaction(fn () => Client::create($data));

        return ApiResponse::success(
            new ClientResource($client->loadCount('projects')),
            'Client created successfully.',
            201
        );
    }

    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        $client->load(['projects' => fn ($query) => $query->with('assignedTo')->latest()])
            ->loadCount('projects')
            ->load('creator');

        return ApiResponse::success(new ClientResource($client));
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $data = $request->validated();
        unset($data['logo'], $data['remove_logo']);

        $oldLogo = $client->logo_path;
        $newLogo = $oldLogo;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('clients', 'uploads');
            $newLogo = $data['logo_path'];
        } elseif ($request->boolean('remove_logo')) {
            $data['logo_path'] = null;
            $newLogo = null;
        }

        DB::transaction(fn () => $client->update($data));

        if ($oldLogo && $oldLogo !== $newLogo) {
            Storage::disk('uploads')->delete($oldLogo);
        }

        return ApiResponse::success(
            new ClientResource($client->fresh()->loadCount('projects')),
            'Client updated successfully.'
        );
    }

    public function destroy(Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        if ($client->projects()->exists()) {
            return ApiResponse::error(
                'This client has projects linked to it. Reassign or delete those projects first.',
                409
            );
        }

        $logo = $client->logo_path;

        DB::transaction(fn () => $client->delete());

        if ($logo) {
            Storage::disk('uploads')->delete($logo);
        }

        return ApiResponse::success(null, 'Client deleted successfully.');
    }
}
