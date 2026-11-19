<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'name' => 'Anish Shrestha',
            'email' => 'admin@sajilocrm.test',
        ]);
        $this->staff = User::factory()->staff()->create([
            'name' => 'Prakriti Adhikari',
            'email' => 'prakriti@sajilocrm.test',
            'job_title' => 'Project Coordinator',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rohan Karki',
            'email' => 'rohan@sajilocrm.test',
            'role' => 'staff',
            'phone' => '+977 9801000203',
            'job_title' => 'Frontend Developer',
            'password' => 'Secret123',
            'password_confirmation' => 'Secret123',
        ], $overrides);
    }

    public function test_admin_can_list_search_and_filter_team_members(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/users?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1);

        $this->getJson('/api/users?q=Coordinator')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'prakriti@sajilocrm.test');

        $this->getJson('/api/users?role=admin')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.email', 'admin@sajilocrm.test');
    }

    public function test_staff_cannot_manage_team_members(): void
    {
        Sanctum::actingAs($this->staff);

        $this->getJson('/api/users')->assertForbidden();
        $this->postJson('/api/users', $this->payload())->assertForbidden();
        $this->putJson('/api/users/'.$this->admin->id, $this->payload())->assertForbidden();
        $this->deleteJson('/api/users/'.$this->admin->id)->assertForbidden();
    }

    public function test_admin_can_create_a_team_member_with_a_hashed_password(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/users', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Rohan Karki')
            ->assertJsonPath('data.role_label', 'Staff')
            ->assertJsonPath('data.assigned_projects_count', 0);

        $user = User::where('email', 'rohan@sajilocrm.test')->firstOrFail();
        $this->assertTrue(Hash::check('Secret123', $user->password));
    }

    public function test_team_member_validation_rejects_duplicates_and_weak_passwords(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/users', $this->payload([
            'email' => 'prakriti@sajilocrm.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_admin_can_update_a_member_without_changing_their_password(): void
    {
        Sanctum::actingAs($this->admin);
        $this->staff->update(['password' => 'Original123']);

        $this->putJson('/api/users/'.$this->staff->id, [
            'name' => 'Prakriti A. Adhikari',
            'email' => 'prakriti@sajilocrm.test',
            'role' => 'admin',
            'phone' => '+977 9801000202',
            'job_title' => 'Senior Coordinator',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Prakriti A. Adhikari')
            ->assertJsonPath('data.role', 'admin');

        $fresh = $this->staff->fresh();
        $this->assertSame('admin', $fresh->role);
        $this->assertTrue(Hash::check('Original123', $fresh->password));
    }

    public function test_admin_can_reset_another_members_password(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/users/'.$this->staff->id, [
            'name' => $this->staff->name,
            'email' => $this->staff->email,
            'role' => 'staff',
            'password' => 'Updated123',
            'password_confirmation' => 'Updated123',
        ])->assertOk();

        $this->assertTrue(Hash::check('Updated123', $this->staff->fresh()->password));
    }

    public function test_admin_cannot_demote_or_delete_themselves(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/users/'.$this->admin->id, [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => 'staff',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->deleteJson('/api/users/'.$this->admin->id)
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_deleting_a_member_unassigns_their_projects(): void
    {
        Sanctum::actingAs($this->admin);
        $project = Project::factory()->create(['assigned_to' => $this->staff->id]);

        $this->deleteJson('/api/users/'.$this->staff->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $this->staff->id]);
        $this->assertNull($project->fresh()->assigned_to);
    }
}
