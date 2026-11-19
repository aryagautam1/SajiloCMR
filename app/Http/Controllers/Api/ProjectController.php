<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Queries\ProjectQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(config('crm.project_statuses')))],
            'priority' => ['nullable', Rule::in(array_keys(config('crm.priorities')))],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'assigned_to' => ['nullable', 'integer', 'min:1'],
            'overdue' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', 'max:30'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $projects = ProjectQuery::build($filters)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return ApiResponse::paginated(ProjectResource::collection($projects));
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['assigned_to'] = $request->user()->isAdmin()
            ? ($data['assigned_to'] ?? null)
            : $request->user()->id;

        $data['progress'] = (int) ($data['progress'] ?? 0);

        if ($data['status'] === 'completed') {
            $data['progress'] = 100;
            $data['completed_at'] = now();
        }

        $project = DB::transaction(fn () => Project::create($data));

        return ApiResponse::success(
            new ProjectResource($project->load(['client', 'assignedTo'])),
            'Project created successfully.',
            201
        );
    }

    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ApiResponse::success(
            new ProjectResource($project->load(['client', 'assignedTo']))
        );
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $data = $request->validated();

        if (! $request->user()->isAdmin()) {
            $data['assigned_to'] = $project->assigned_to;
        } else {
            $data['assigned_to'] = $data['assigned_to'] ?? null;
        }

        $data['progress'] = (int) ($data['progress'] ?? 0);

        if ($data['status'] === 'completed') {
            $data['progress'] = 100;
            $data['completed_at'] = $project->completed_at ?? now();
        } else {
            $data['completed_at'] = null;
        }

        DB::transaction(fn () => $project->update($data));

        return ApiResponse::success(
            new ProjectResource($project->fresh()->load(['client', 'assignedTo'])),
            'Project updated successfully.'
        );
    }

    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        DB::transaction(fn () => $project->delete());

        return ApiResponse::success(null, 'Project deleted successfully.');
    }
}
