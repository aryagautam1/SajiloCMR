<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_clients_export_streams_csv_with_all_records(): void
    {
        Client::factory()->active()->create([
            'company_name' => 'Everest Trails',
            'created_by' => $this->admin->id,
        ]);
        Client::factory()->inactive()->create([
            'company_name' => 'Quiet Corner',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->get('/clients/export');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('sajilo-clients-'.now()->format('Y-m-d').'.csv');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('ID,"Contact Name",Company,Email,Phone,Address,Status,Projects,"Created At"', $csv);
        $this->assertStringContainsString('Everest Trails', $csv);
        $this->assertStringContainsString('Quiet Corner', $csv);
        $this->assertStringContainsString('Active', $csv);
        $this->assertStringContainsString('Inactive', $csv);
    }

    public function test_clients_export_respects_the_status_filter(): void
    {
        Client::factory()->active()->create([
            'company_name' => 'Everest Trails',
            'created_by' => $this->admin->id,
        ]);
        Client::factory()->inactive()->create([
            'company_name' => 'Quiet Corner',
            'created_by' => $this->admin->id,
        ]);

        $csv = $this->get('/clients/export?status=active')->assertOk()->streamedContent();

        $this->assertStringContainsString('Everest Trails', $csv);
        $this->assertStringNotContainsString('Quiet Corner', $csv);
    }

    public function test_clients_export_escapes_spreadsheet_formulas(): void
    {
        Client::factory()->create([
            'name' => '=HYPERLINK("http://example.test")',
            'company_name' => 'Formula Co',
            'created_by' => $this->admin->id,
        ]);

        $csv = $this->get('/clients/export')->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_projects_export_includes_relationships_and_respects_filters(): void
    {
        $client = Client::factory()->create([
            'company_name' => 'Kathmandu Codeworks',
            'created_by' => $this->admin->id,
        ]);

        $completed = Project::factory()->completed()->create([
            'client_id' => $client->id,
            'assigned_to' => $this->admin->id,
            'project_name' => 'Completed Website',
            'budget' => 125000,
        ]);

        Project::factory()->create([
            'client_id' => $client->id,
            'assigned_to' => $this->admin->id,
            'project_name' => 'Ongoing Portal',
            'status' => 'in_progress',
        ]);

        $response = $this->get('/projects/export?status=completed');

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload('sajilo-projects-'.now()->format('Y-m-d').'.csv');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('"Project ID",Project,Client,Contact,Status,Priority', $csv);
        $this->assertStringContainsString('PRJ-'.str_pad((string) $completed->id, 4, '0', STR_PAD_LEFT), $csv);
        $this->assertStringContainsString('Completed Website', $csv);
        $this->assertStringContainsString('Kathmandu Codeworks', $csv);
        $this->assertStringContainsString('Completed', $csv);
        $this->assertStringNotContainsString('Ongoing Portal', $csv);
    }

    public function test_guests_cannot_download_exports(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get('/clients/export')->assertRedirect('/login');
        $this->get('/projects/export')->assertRedirect('/login');
    }
}
