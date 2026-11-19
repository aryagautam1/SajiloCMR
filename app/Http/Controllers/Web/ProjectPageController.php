<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\View\View;

class ProjectPageController extends Controller
{
    public function index(): View
    {
        return view('projects.index', [
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name', 'name']),
            'staff' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['client', 'assignedTo']);

        return view('projects.show', [
            'project' => $project,
            'projectData' => (new ProjectResource($project))->resolve(),
            'clients' => Client::query()->orderBy('company_name')->get(['id', 'company_name', 'name']),
            'staff' => User::query()->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }
}
