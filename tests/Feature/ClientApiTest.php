<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientApiTest extends TestCase
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
        ]);

        Sanctum::actingAs($this->admin);
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Sneha Gurung',
            'company_name' => 'Kathmandu Codeworks',
            'email' => 'sneha@ktmcodeworks.example',
            'phone' => '+977 9801234502',
            'address' => 'Lalitpur, Bagmati',
            'status' => 'active',
            'notes' => 'Long-term retainer client.',
        ], $overrides);
    }

    public function test_client_index_is_paginated_searchable_and_filterable(): void
    {
        Client::factory()->active()->create(['company_name' => 'Everest Trails', 'created_by' => $this->admin->id]);
        Client::factory()->inactive()->create(['company_name' => 'Quiet Corner', 'created_by' => $this->admin->id]);
        Client::factory()->prospect()->create(['company_name' => 'Lakeside Resort', 'created_by' => $this->admin->id]);

        $this->getJson('/api/clients?per_page=2')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2);

        $this->getJson('/api/clients?q=Everest')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'Everest Trails');

        $this->getJson('/api/clients?status=prospect')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'Lakeside Resort');

        $firstCompany = $this->getJson('/api/clients?sort=company_name&direction=asc')
            ->assertOk()
            ->json('data.0.company_name');

        $this->assertSame('Everest Trails', $firstCompany);
    }

    public function test_admin_can_create_a_client(): void
    {
        $response = $this->postJson('/api/clients', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Client created successfully.')
            ->assertJsonPath('data.company_name', 'Kathmandu Codeworks')
            ->assertJsonPath('data.projects_count', 0);

        $this->assertDatabaseHas('clients', [
            'email' => 'sneha@ktmcodeworks.example',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_client_logo_is_stored_on_the_uploads_disk(): void
    {
        Storage::fake('uploads');

        $response = $this->post('/api/clients', $this->payload([
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
        ]), ['Accept' => 'application/json']);

        $response->assertCreated();

        $client = Client::where('email', 'sneha@ktmcodeworks.example')->firstOrFail();
        $this->assertNotNull($client->logo_path);
        $this->assertStringStartsWith('clients/', $client->logo_path);
        Storage::disk('uploads')->assertExists($client->logo_path);
        $response->assertJsonPath('data.logo_url', Storage::disk('uploads')->url($client->logo_path));
    }

    public function test_client_validation_rejects_bad_input(): void
    {
        $this->postJson('/api/clients', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'company_name', 'email', 'phone', 'status']);

        Client::factory()->create(['email' => 'taken@example.test']);

        $this->postJson('/api/clients', $this->payload([
            'email' => 'taken@example.test',
            'phone' => 'not-a-phone',
            'status' => 'unknown',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'phone', 'status']);
    }

    public function test_client_detail_includes_projects_and_creator(): void
    {
        $client = Client::factory()->create(['created_by' => $this->admin->id]);
        Project::factory()->count(2)->create([
            'client_id' => $client->id,
            'assigned_to' => $this->staff->id,
        ]);

        $this->getJson('/api/clients/'.$client->id)
            ->assertOk()
            ->assertJsonPath('data.id', $client->id)
            ->assertJsonPath('data.projects_count', 2)
            ->assertJsonCount(2, 'data.projects')
            ->assertJsonPath('data.creator.email', 'admin@sajilocrm.test');
    }

    public function test_admin_can_update_a_client(): void
    {
        $client = Client::factory()->create(['created_by' => $this->admin->id]);

        $this->putJson('/api/clients/'.$client->id, $this->payload([
            'company_name' => 'Codeworks Nepal',
            'status' => 'inactive',
        ]))
            ->assertOk()
            ->assertJsonPath('data.company_name', 'Codeworks Nepal')
            ->assertJsonPath('data.status_label', 'Inactive');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'company_name' => 'Codeworks Nepal',
            'status' => 'inactive',
        ]);
    }

    public function test_staff_can_create_and_update_clients_but_cannot_delete_them(): void
    {
        Sanctum::actingAs($this->staff);

        $clientId = $this->postJson('/api/clients', $this->payload())
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('clients', [
            'id' => $clientId,
            'created_by' => $this->staff->id,
        ]);

        $this->putJson('/api/clients/'.$clientId, $this->payload(['name' => 'Sneha G.']))
            ->assertOk()
            ->assertJsonPath('data.name', 'Sneha G.');

        $this->deleteJson('/api/clients/'.$clientId)->assertForbidden();

        $this->assertDatabaseHas('clients', ['id' => $clientId]);
    }

    public function test_admin_can_delete_a_client_without_projects(): void
    {
        $client = Client::factory()->create();

        $this->deleteJson('/api/clients/'.$client->id)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->deleteJson('/api/clients/'.$client->id)->assertNotFound();
    }

    public function test_client_with_projects_cannot_be_deleted(): void
    {
        $client = Client::factory()->create();
        Project::factory()->create(['client_id' => $client->id, 'assigned_to' => $this->staff->id]);

        $this->deleteJson('/api/clients/'.$client->id)
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }
}
