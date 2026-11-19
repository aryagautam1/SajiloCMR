<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Queries\ProjectQuery;
use App\Support\Csv;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(config('crm.project_statuses')))],
            'priority' => ['nullable', Rule::in(array_keys(config('crm.priorities')))],
            'client_id' => ['nullable', 'integer', 'min:1'],
            'assigned_to' => ['nullable', 'integer', 'min:1'],
            'overdue' => ['nullable', 'boolean'],
        ]);

        $projects = ProjectQuery::build($filters)
            ->reorder()
            ->orderBy('project_name')
            ->get();

        $rows = $projects->map(fn (Project $project) => [
            $project->code(),
            $project->project_name,
            $project->client?->company_name,
            $project->client?->name,
            $project->statusLabel(),
            $project->priorityLabel(),
            $project->start_date?->format('Y-m-d'),
            $project->expected_completion_date?->format('Y-m-d'),
            $project->budget,
            $project->progress.'%',
            $project->assignedTo?->name,
            $project->created_at?->format('Y-m-d H:i'),
        ]);

        return Csv::download(
            'sajilo-projects-'.now()->format('Y-m-d').'.csv',
            ['Project ID', 'Project', 'Client', 'Contact', 'Status', 'Priority', 'Start Date', 'Expected Completion', 'Budget (NPR)', 'Progress', 'Assigned To', 'Created At'],
            $rows
        );
    }
}
