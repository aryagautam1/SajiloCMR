<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Support\Csv;
use Tests\TestCase;

class ModelHelpersTest extends TestCase
{
    public function test_client_initials_use_company_name_first(): void
    {
        $client = new Client(['name' => 'Sneha Gurung', 'company_name' => 'Kathmandu Codeworks']);
        $this->assertSame('KC', $client->initials());

        $client = new Client(['name' => 'Sneha Gurung', 'company_name' => null]);
        $this->assertSame('SG', $client->initials());

        $client = new Client(['name' => 'Sneha', 'company_name' => '']);
        $this->assertSame('S', $client->initials());
    }

    public function test_client_status_label_uses_configuration(): void
    {
        $this->assertSame('Active', (new Client(['status' => 'active']))->statusLabel());
        $this->assertSame('Inactive', (new Client(['status' => 'inactive']))->statusLabel());
        $this->assertSame('Prospect', (new Client(['status' => 'prospect']))->statusLabel());
    }

    public function test_project_code_is_padded_from_the_id(): void
    {
        $project = new Project;
        $project->id = 1;
        $this->assertSame('PRJ-0001', $project->code());

        $project->id = 42;
        $this->assertSame('PRJ-0042', $project->code());

        $project->id = 12345;
        $this->assertSame('PRJ-12345', $project->code());
    }

    public function test_project_overdue_logic(): void
    {
        $overdue = Project::make([
            'status' => 'in_progress',
            'expected_completion_date' => now()->subDay()->toDateString(),
        ]);
        $this->assertTrue($overdue->isOverdue());

        $completed = Project::make([
            'status' => 'completed',
            'expected_completion_date' => now()->subDay()->toDateString(),
        ]);
        $this->assertFalse($completed->isOverdue());

        $cancelled = Project::make([
            'status' => 'cancelled',
            'expected_completion_date' => now()->subDay()->toDateString(),
        ]);
        $this->assertFalse($cancelled->isOverdue());

        $noDate = Project::make(['status' => 'in_progress', 'expected_completion_date' => null]);
        $this->assertFalse($noDate->isOverdue());

        $future = Project::make([
            'status' => 'in_progress',
            'expected_completion_date' => now()->addWeek()->toDateString(),
        ]);
        $this->assertFalse($future->isOverdue());
    }

    public function test_project_status_and_priority_labels_use_configuration(): void
    {
        $project = Project::make(['status' => 'on_hold', 'priority' => 'urgent']);

        $this->assertSame('On Hold', $project->statusLabel());
        $this->assertSame('Urgent', $project->priorityLabel());
    }

    public function test_user_initials_and_admin_flag(): void
    {
        $user = new User(['name' => 'Anish Shrestha', 'role' => 'admin']);

        $this->assertSame('AS', $user->initials());
        $this->assertTrue($user->isAdmin());

        $user = new User(['name' => 'Prakriti Adhikari', 'role' => 'staff']);
        $this->assertFalse($user->isAdmin());
    }

    public function test_csv_safe_escapes_formula_injection_only(): void
    {
        $this->assertSame("'=SUM(A1:A2)", Csv::safe('=SUM(A1:A2)'));
        $this->assertSame("'+977 9801234502", Csv::safe('+977 9801234502'));
        $this->assertSame("'-NPR", Csv::safe('-NPR'));
        $this->assertSame("'@user", Csv::safe('@user'));
        $this->assertSame('Kathmandu Codeworks', Csv::safe('Kathmandu Codeworks'));
        $this->assertSame('', Csv::safe(null));
        $this->assertSame('0', Csv::safe(0));
    }
}
