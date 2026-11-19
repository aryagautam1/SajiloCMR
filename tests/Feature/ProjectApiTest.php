<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private User $otherStaff;

    private Client $client;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->staff = User::factory()->staff()->create(['name' => 'Prakriti Adhikari']);
        $this->otherStaff = User::factory()->staff()->create(['name' => 'Rohan Karki']);
        $this->client = Client::factory()->create(['created_by' => $this->admin->id]);

        $this->project = Project::factory()->create([
            'client_id' => $this->client->id,
            'assigned_to' => $this->staff->id,
            'project_name' => 'Corporate Website Redesign',
            'status' => 'in_progress',
            'priority' => 'urgent',
            'progress' => 40,
            'budget' => 200000,
            'start_date' => now()->subMonth()->toDateString(),
            'expected_completion_date' => now()->addMonth()->toDateString(),
        ]);

        Sanctum::actingAs($this->admin);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => $this->client->id,
            'assigned_to' => $this->staff->id,
            'project_name' => 'New Project',
            'description' => 'A project created by the test suite.',
            'status' => 'planning',
            'priority' => 'medium',
            'start_date' => now()->toDateString(),
            'expected_completion_date' => now()->addMonths(2)->toDateString(),
            'budget' => 150000,
            'progress' => 10,
        ], $overrides);
    }

    public function test_project_index_supports_filters_search_and_pagination(): void
    {
        Project::factory()->create([
            'client_id' => $this->client->id,
            'assigned_to' => $this->otherStaff->id,
            'project_name' => 'Quiet Planning Work',
            'status' => 'planning',
            'priority' => 'low',
        ]);

        $this->getJson('/api/projects?status=in_progress&priority=urgent&q=Corporate')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->project->id);

        $this->getJson('/api/projects?client_id='.$this->client->id.'&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/projects?assigned_to='.$this->otherStaff->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.project_name', 'Quiet Planning Work');
    }

    public function test_overdue_filter_returns_only_past_due_active_projects(): void
    {
        $overdue = Project::factory()->create([
            'client_id' => $this->client->id,
            'assigned_to' => $this->staff->id,
            'status' => 'in_progress',
            'expected_completion_date' => now()->subDays(5)->toDateString(),
        ]);

        $completedPast = Project::factory()->completed()->create([
            'client_id' => $this->client->id,
            'assigned_to' => $this->staff->id,
            'expected_completion_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->getJson('/api/projects?overdue=1')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($overdue->id));
        $this->assertFalse($ids->contains($completedPast->id));
        $this->assertFalse($ids->contains($this->project->id));
    }

    public function test_staff_created_project_is_automatically_assigned_to_them(): void
    {
        Sanctum::actingAs($this->staff);

        $response = $this->postJson('/api/projects', $this->payload([
            'assigned_to' => $this->admin->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.assigned_to.id', $this->staff->id)
            ->assertJsonPath('data.client.id', $this->client->id);

        $this->assertDatabaseHas('projects', [
            'project_name' => 'New Project',
            'assigned_to' => $this->staff->id,
        ]);
    }

    public function test_admin_can_assign_a_project_to_any_team_member(): void
    {
        $this->postJson('/api/projects', $this->payload(['assigned_to' => $this->otherStaff->id]))
            ->assertCreated()
            ->assertJsonPath('data.assigned_to.id', $this->otherStaff->id);
    }

    public function test_project_validation_rejects_bad_dates_and_unknown_clients(): void
    {
        $this->postJson('/api/projects', $this->payload([
            'client_id' => 9999,
            'start_date' => '2026-12-10',
            'expected_completion_date' => '2026-11-01',
            'status' => 'unknown',
            'priority' => 'nope',
            'progress' => 150,
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'client_id',
                'expected_completion_date',
                'status',
                'priority',
                'progress',
            ]);
    }

    public function test_staff_can_update_an_assigned_project_but_not_reassign_it(): void
    {
        Sanctum::actingAs($this->staff);

        $this->putJson('/api/projects/'.$this->project->id, $this->payload([
            'project_name' => 'Corporate Website Redesign v2',
            'assigned_to' => $this->admin->id,
            'progress' => 65,
        ]))
            ->assertOk()
            ->assertJsonPath('data.project_name', 'Corporate Website Redesign v2')
            ->assertJsonPath('data.progress', 65)
            ->assertJsonPath('data.assigned_to.id', $this->staff->id);

        $this->assertDatabaseHas('projects', [
            'id' => $this->project->id,
            'assigned_to' => $this->staff->id,
        ]);
    }

    public function test_staff_cannot_update_a_project_assigned_to_someone_else(): void
    {
        $otherProject = Project::factory()->create([
            'client_id' => $this->client->id,
            'assigned_to' => $this->otherStaff->id,
        ]);

        Sanctum::actingAs($this->staff);

        $this->putJson('/api/projects/'.$otherProject->id, $this->payload())
            ->assertForbidden();
    }

    public function test_marking_a_project_complete_sets_progress_and_timestamp_and_reopening_clears_it(): void
    {
        $this->putJson('/api/projects/'.$this->project->id, $this->payload([
            'status' => 'completed',
            'progress' => 20,
        ]))
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100);

        $project = $this->project->fresh();
        $this->assertNotNull($project->completed_at);

        $this->putJson('/api/projects/'.$this->project->id, $this->payload([
            'status' => 'in_progress',
            'progress' => 75,
        ]))
            ->assertOk()
            ->assertJsonPath('data.progress', 75);

        $this->assertNull($this->project->fresh()->completed_at);
    }

    public function test_project_detail_includes_client_and_assignee(): void
    {
        $this->getJson('/api/projects/'.$this->project->id)
            ->assertOk()
            ->assertJsonPath('data.code', 'PRJ-'.str_pad((string) $this->project->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.client.id', $this->client->id)
            ->assertJsonPath('data.assigned_to.id', $this->staff->id)
            ->assertJsonPath('data.budget', 200000);
    }

    public function test_only_admins_can_delete_projects(): void
    {
        Sanctum::actingAs($this->staff);
        $this->deleteJson('/api/projects/'.$this->project->id)->assertForbidden();

        Sanctum::actingAs($this->admin);
        $this->deleteJson('/api/projects/'.$this->project->id)->assertOk();

        $this->assertDatabaseMissing('projects', ['id' => $this->project->id]);
        $this->deleteJson('/api/projects/'.$this->project->id)->assertNotFound();
    }
}
