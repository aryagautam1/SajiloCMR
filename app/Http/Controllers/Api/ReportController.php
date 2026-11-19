<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Aggregated portfolio metrics for the reports page and reporting clients.
     */
    public function summary(): JsonResponse
    {
        $activeStatuses = ['planning', 'not_started', 'in_progress', 'on_hold'];

        $projectRows = Project::query()
            ->select('status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(budget), 0) as budget'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $clientRows = Client::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $priorityRows = Project::query()
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority');

        $totalProjects = (int) $projectRows->sum('total');
        $completedRow = $projectRows->get('completed');
        $completedProjects = (int) ($completedRow->total ?? 0);
        $activeProjects = (int) collect($activeStatuses)->sum(fn (string $status) => (int) ($projectRows->get($status)->total ?? 0));
        $totalBudget = (float) $projectRows->sum('budget');
        $activeBudget = (float) collect($activeStatuses)->sum(fn (string $status) => (float) ($projectRows->get($status)->budget ?? 0));
        $completedBudget = (float) ($completedRow->budget ?? 0);

        $team = User::query()
            ->select(['id', 'name', 'role', 'job_title'])
            ->withCount([
                'assignedProjects as total_projects',
                'assignedProjects as active_projects' => fn (Builder $query) => $query->whereIn('status', $activeStatuses),
                'assignedProjects as completed_projects' => fn (Builder $query) => $query->where('status', 'completed'),
            ])
            ->withSum('assignedProjects as portfolio_budget', 'budget')
            ->orderByDesc('total_projects')
            ->orderBy('name')
            ->get();

        $topClients = Client::query()
            ->select(['id', 'company_name', 'name', 'email', 'phone', 'status'])
            ->withCount([
                'projects as projects_count',
                'projects as active_projects' => fn (Builder $query) => $query->whereIn('status', $activeStatuses),
                'projects as completed_projects' => fn (Builder $query) => $query->where('status', 'completed'),
            ])
            ->withSum('projects as portfolio_budget', 'budget')
            ->orderByDesc('projects_count')
            ->orderBy('company_name')
            ->limit(8)
            ->get();

        $recentCompletions = Project::query()
            ->with('client')
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(5)
            ->get();

        $months = collect(range(11, 0))
            ->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset))
            ->values();

        $start = $months->first()->copy()->startOfMonth();

        $createdByMonth = Project::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->groupBy(fn (Project $project) => $project->created_at->format('Y-m'))
            ->map->count();

        $completedByMonth = Project::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->get(['completed_at'])
            ->groupBy(fn (Project $project) => $project->completed_at->format('Y-m'))
            ->map->count();

        return ApiResponse::success([
            'generated_at' => now()->toIso8601String(),
            'kpis' => [
                'total_clients' => (int) $clientRows->sum(),
                'total_projects' => $totalProjects,
                'active_projects' => $activeProjects,
                'completed_projects' => $completedProjects,
                'overdue_projects' => Project::query()
                    ->whereNotNull('expected_completion_date')
                    ->whereDate('expected_completion_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'completion_rate' => $totalProjects > 0 ? (int) round(($completedProjects / $totalProjects) * 100) : 0,
                'average_progress' => (int) round((float) Project::query()
                    ->whereNotIn('status', ['cancelled'])
                    ->avg('progress')),
                'total_budget' => $totalBudget,
                'active_budget' => $activeBudget,
                'completed_budget' => $completedBudget,
            ],
            'project_status' => $this->series(config('crm.project_statuses'), $projectRows->map(fn ($row) => (int) $row->total)),
            'client_status' => $this->series(config('crm.client_statuses'), $clientRows),
            'priority' => $this->series(config('crm.priorities'), $priorityRows),
            'budget_by_status' => collect(config('crm.project_statuses'))
                ->map(function (array $definition, string $key) use ($projectRows) {
                    $row = $projectRows[$key] ?? null;

                    return [
                        'key' => $key,
                        'label' => $definition['label'],
                        'count' => (int) ($row->total ?? 0),
                        'budget' => (float) ($row->budget ?? 0),
                        'color' => config("crm.chart_colors.{$key}", '#94a3b8'),
                    ];
                })
                ->values()
                ->all(),
            'monthly_activity' => [
                'labels' => $months->map(fn ($month) => $month->format('M Y'))->all(),
                'created' => $months->map(fn ($month) => (int) ($createdByMonth[$month->format('Y-m')] ?? 0))->all(),
                'completed' => $months->map(fn ($month) => (int) ($completedByMonth[$month->format('Y-m')] ?? 0))->all(),
            ],
            'team_workload' => $team->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'initials' => $member->initials(),
                'role' => $member->role,
                'role_label' => $member->isAdmin() ? 'Administrator' : 'Staff',
                'job_title' => $member->job_title,
                'total_projects' => (int) $member->total_projects,
                'active_projects' => (int) $member->active_projects,
                'completed_projects' => (int) $member->completed_projects,
                'portfolio_budget' => (float) ($member->portfolio_budget ?? 0),
            ])->all(),
            'top_clients' => $topClients->map(fn (Client $client) => [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'status' => $client->status,
                'status_label' => $client->statusLabel(),
                'projects_count' => (int) $client->projects_count,
                'active_projects' => (int) $client->active_projects,
                'completed_projects' => (int) $client->completed_projects,
                'portfolio_budget' => (float) ($client->portfolio_budget ?? 0),
            ])->all(),
            'recent_completions' => $recentCompletions->map(fn (Project $project) => [
                'id' => $project->id,
                'code' => $project->code(),
                'project_name' => $project->project_name,
                'client' => $project->client?->company_name,
                'completed_at' => $project->completed_at?->toDateString(),
                'budget' => $project->budget !== null ? (float) $project->budget : null,
            ])->all(),
        ]);
    }

    /**
     * @param  array<string, array{label: string, tone: string}>  $definitions
     * @param  Collection<string, int|string>  $counts
     * @return array<int, array{key: string, label: string, count: int, color: string}>
     */
    private function series(array $definitions, $counts): array
    {
        return collect($definitions)
            ->map(fn (array $definition, string $key) => [
                'key' => $key,
                'label' => $definition['label'],
                'count' => (int) ($counts[$key] ?? 0),
                'color' => config("crm.chart_colors.{$key}", '#94a3b8'),
            ])
            ->values()
            ->all();
    }
}
