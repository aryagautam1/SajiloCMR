<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['admin', 'staff'])],
            'sort' => ['nullable', Rule::in(['name', 'email', 'role', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()->withCount('assignedProjects');

        if (! empty($filters['q'])) {
            $term = '%'.trim($filters['q']).'%';
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('job_title', 'like', $term);
            });
        }

        if (! empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? ($sort === 'created_at' ? 'desc' : 'asc');
        $direction = $direction === 'asc' ? 'asc' : 'desc';

        $users = $query->orderBy($sort, $direction)
            ->paginate($filters['per_page'] ?? 10)
            ->withQueryString();

        return ApiResponse::paginated(UserResource::collection($users));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::create($data);

        return ApiResponse::success(
            new UserResource($user->loadCount('assignedProjects')),
            'Team member added successfully.',
            201
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if ($user->id === $request->user()->id && $data['role'] !== User::ROLE_ADMIN) {
            return ApiResponse::error('You cannot remove your own administrator role.', 422);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return ApiResponse::success(
            new UserResource($user->fresh()->loadCount('assignedProjects')),
            'Team member updated successfully.'
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return ApiResponse::error('You cannot delete your own account.', 422);
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return ApiResponse::error('The last administrator account cannot be deleted.', 422);
        }

        $user->delete();

        return ApiResponse::success(null, 'Team member removed successfully.');
    }
}
