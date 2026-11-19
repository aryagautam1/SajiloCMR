<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_protected_pages(): void
    {
        foreach (['/', '/clients', '/projects', '/reports', '/settings'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_login_page_loads_the_sign_in_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('Email address')
            ->assertSee('Password');
    }

    public function test_authenticated_pages_render_for_every_signed_in_user(): void
    {
        $admin = User::factory()->admin()->create();
        $client = Client::factory()->create(['created_by' => $admin->id]);
        $project = Project::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => $admin->id,
            'project_name' => 'Corporate Website Redesign',
        ]);

        $this->actingAs($admin);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('dashboardStats');

        $this->get('/clients')
            ->assertOk()
            ->assertSee('Clients')
            ->assertSee('clientsTable');

        $this->get('/projects')
            ->assertOk()
            ->assertSee('Projects')
            ->assertSee('projectsTable');

        $this->get('/reports')
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('reportsPage');

        $this->get('/settings')
            ->assertOk()
            ->assertSee('Profile &amp; settings', false)
            ->assertSee('settingsPage');

        $this->get('/projects/'.$project->id)
            ->assertOk()
            ->assertSee('Corporate Website Redesign')
            ->assertSee('projectTabs');

        $this->get('/users')
            ->assertOk()
            ->assertSee('Team members')
            ->assertSee('usersTable');
    }

    public function test_staff_cannot_open_the_team_page(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get('/users')
            ->assertForbidden()
            ->assertSee('Access denied');
    }

    public function test_unknown_project_renders_the_not_found_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/projects/99999')
            ->assertNotFound()
            ->assertSee('Page not found');
    }
}
