<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardAndReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private Client $activeClient;

    private Client $inactiveClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->staff()->create();

        $this->activeClient = Client::factory()->active()->create([
            'company_name' => 'Kathmandu Codeworks',
            'created_by' => $this->admin->id,
        ]);
        $this->inactiveClient = Client::factory()->inactive()->create([
            'company_name' => 'Quiet Corner',
            'created_by' => $this->admin->id,
        ]);
        Client::factory()->prospect()->create(['created_by' => $this->admin->id]);

        Project::factory()->completed()->create([
            'client_id' => $this->activeClient->id,
            'assigned_to' => $this->staff->id,
            'project_name' => 'Completed Website',
            'budget' => 1000,
            'completed_at' => now()->subDays(5),
        ]);

        Project::factory()->create([
            'client_id' => $this->activeClient->id,
            'assigned_to' => $this->staff->id,
            'project_name' => 'Overdue Campaign',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'progress' => 50,
            'budget' => 2000,
            'expected_completion_date' => now()->subDays(2)->toDateString(),
        ]);

        Project::factory()->create([
            'client_id' => $this->inactiveClient->id,
            'assigned_to' => null,
            'project_name' => 'Planned Portal',
            'status' => 'planning',
            'priority' => 'low',
            'progress' => 10,
            'budget' => 500,
            'expected_completion_date' => now()->addMonth()->toDateString(),
        ]);

        Project::factory()->create([
            'client_id' => $this->inactiveClient->id,
            'assigned_to' => null,
            'project_name' => 'Cancelled Work',
            'status' => 'cancelled',
            'priority' => 'medium',
            'progress' => 0,
            'budget' => 250,
        ]);

        Sanctum::actingAs($this->admin);
    }

    public function test_dashboard_statistics_are_calculated_from_the_database(): void
    {
        $response = $this->getJson('/api/dashboard/statistics')->assertOk();

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.cards.total_clients', 3)
            ->assertJsonPath('data.cards.active_clients', 1)
            ->assertJsonPath('data.cards.inactive_clients', 1)
            ->assertJsonPath('data.cards.prospect_clients', 1)
            ->assertJsonPath('data.cards.total_projects', 4)
            ->assertJsonPath('data.cards.ongoing_projects', 1)
            ->assertJsonPath('data.cards.completed_projects', 1)
            ->assertJsonPath('data.cards.pending_projects', 1)
            ->assertJsonPath('data.cards.cancelled_projects', 1)
            ->assertJsonPath('data.cards.overdue_projects', 1)
            ->assertJsonPath('data.cards.total_budget', 3750)
            ->assertJsonPath('data.cards.active_budget', 2500)
            ->assertJsonPath('data.cards.team_members', 2)
            ->assertJsonPath('data.client_status_counts.active', 1)
            ->assertJsonPath('data.project_status_counts.completed', 1)
            ->assertJsonCount(4, 'data.recent_projects')
            ->assertJsonCount(3, 'data.recent_clients');
    }

    public function test_dashboard_charts_return_all_series(): void
    {
        $this->getJson('/api/dashboard/charts')
            ->assertOk()
            ->assertJsonCount(6, 'data.project_status')
            ->assertJsonCount(3, 'data.client_status')
            ->assertJsonCount(4, 'data.priority')
            ->assertJsonCount(12, 'data.monthly_activity.labels')
            ->assertJsonCount(12, 'data.monthly_activity.created')
            ->assertJsonCount(12, 'data.monthly_activity.completed');

        $projectSeries = collect($this->getJson('/api/dashboard/charts')->json('data.project_status'));
        $this->assertSame(4, $projectSeries->sum('count'));
        $this->assertSame(1, $projectSeries->firstWhere('key', 'completed')['count']);
    }

    public function test_report_summary_aggregates_portfolio_metrics(): void
    {
        $response = $this->getJson('/api/reports/summary')->assertOk();

        $response->assertJsonPath('data.kpis.total_clients', 3)
            ->assertJsonPath('data.kpis.total_projects', 4)
            ->assertJsonPath('data.kpis.active_projects', 2)
            ->assertJsonPath('data.kpis.completed_projects', 1)
            ->assertJsonPath('data.kpis.overdue_projects', 1)
            ->assertJsonPath('data.kpis.completion_rate', 25)
            ->assertJsonPath('data.kpis.average_progress', 53)
            ->assertJsonPath('data.kpis.total_budget', 3750)
            ->assertJsonPath('data.kpis.active_budget', 2500)
            ->assertJsonPath('data.kpis.completed_budget', 1000)
            ->assertJsonPath('data.top_clients.0.company_name', 'Kathmandu Codeworks')
            ->assertJsonPath('data.top_clients.0.projects_count', 2)
            ->assertJsonCount(2, 'data.team_workload')
            ->assertJsonCount(1, 'data.recent_completions')
            ->assertJsonCount(6, 'data.project_status')
            ->assertJsonCount(3, 'data.client_status')
            ->assertJsonCount(4, 'data.priority')
            ->assertJsonCount(6, 'data.budget_by_status')
            ->assertJsonCount(12, 'data.monthly_activity.labels');

        $staffWorkload = collect($response->json('data.team_workload'))
            ->firstWhere('id', $this->staff->id);

        $this->assertSame(2, $staffWorkload['total_projects']);
        $this->assertSame(1, $staffWorkload['active_projects']);
        $this->assertSame(1, $staffWorkload['completed_projects']);
        $this->assertSame(3000.0, (float) $staffWorkload['portfolio_budget']);
    }

    public function test_staff_can_view_reports_but_guests_cannot(): void
    {
        Sanctum::actingAs($this->staff);
        $this->getJson('/api/reports/summary')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/reports/summary')->assertStatus(401);
    }
}
