<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClientResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function statistics(): JsonResponse
    {
        $clientCounts = Client::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $projectCounts = Project::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalClients = (int) $clientCounts->sum();
        $totalProjects = (int) $projectCounts->sum();

        $recentProjects = Project::query()
            ->with(['client', 'assignedTo'])
            ->latest()
            ->limit(5)
            ->get();

        $recentClients = Client::query()
            ->withCount('projects')
            ->latest()
            ->limit(5)
            ->get();

        $progressOverview = Project::query()
            ->with('client')
            ->whereIn('status', ['planning', 'not_started', 'in_progress', 'on_hold'])
            ->orderByDesc('progress')
            ->limit(6)
            ->get();

        $activeStatuses = ['planning', 'not_started', 'in_progress', 'on_hold'];

        return ApiResponse::success([
            'cards' => [
                'total_clients' => $totalClients,
                'active_clients' => (int) ($clientCounts['active'] ?? 0),
                'inactive_clients' => (int) ($clientCounts['inactive'] ?? 0),
                'prospect_clients' => (int) ($clientCounts['prospect'] ?? 0),
                'total_projects' => $totalProjects,
                'ongoing_projects' => (int) ($projectCounts['in_progress'] ?? 0),
                'completed_projects' => (int) ($projectCounts['completed'] ?? 0),
                'pending_projects' => (int) (($projectCounts['planning'] ?? 0) + ($projectCounts['not_started'] ?? 0)),
                'on_hold_projects' => (int) ($projectCounts['on_hold'] ?? 0),
                'cancelled_projects' => (int) ($projectCounts['cancelled'] ?? 0),
                'overdue_projects' => Project::query()
                    ->whereNotNull('expected_completion_date')
                    ->whereDate('expected_completion_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->count(),
                'total_budget' => (float) Project::whereNotNull('budget')->sum('budget'),
                'active_budget' => (float) Project::whereIn('status', $activeStatuses)->sum('budget'),
                'team_members' => User::count(),
            ],
            'client_status_counts' => $this->countsFor(config('crm.client_statuses'), $clientCounts),
            'project_status_counts' => $this->countsFor(config('crm.project_statuses'), $projectCounts),
            'recent_projects' => ProjectSummaryResource::collection($recentProjects)->resolve(),
            'recent_clients' => ClientResource::collection($recentClients)->resolve(),
            'progress_overview' => ProjectSummaryResource::collection($progressOverview)->resolve(),
        ]);
    }

    public function charts(): JsonResponse
    {
        $projectCounts = Project::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $clientCounts = Client::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $priorityCounts = Project::query()
            ->select('priority', DB::raw('COUNT(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority');

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
            'project_status' => $this->chartSeries(config('crm.project_statuses'), $projectCounts),
            'client_status' => $this->chartSeries(config('crm.client_statuses'), $clientCounts),
            'priority' => $this->chartSeries(config('crm.priorities'), $priorityCounts),
            'monthly_activity' => [
                'labels' => $months->map(fn ($month) => $month->format('M Y'))->all(),
                'created' => $months->map(fn ($month) => (int) ($createdByMonth[$month->format('Y-m')] ?? 0))->all(),
                'completed' => $months->map(fn ($month) => (int) ($completedByMonth[$month->format('Y-m')] ?? 0))->all(),
            ],
        ]);
    }

    /**
     * @param  array<string, array{label: string, tone: string}>  $definitions
     * @param  Collection<string, int|string>  $counts
     * @return array<int, array{key: string, label: string, count: int, color: string}>
     */
    private function chartSeries(array $definitions, $counts): array
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

    /**
     * @param  array<string, array{label: string, tone: string}>  $definitions
     * @param  Collection<string, int|string>  $counts
     * @return array<string, int>
     */
    private function countsFor(array $definitions, $counts): array
    {
        return collect($definitions)
            ->mapWithKeys(fn (array $definition, string $key) => [$key => (int) ($counts[$key] ?? 0)])
            ->all();
    }
}
