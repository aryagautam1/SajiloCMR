<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\Concerns\SetsCreatedAt;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    use SetsCreatedAt;

    public function run(): void
    {
        $clients = Client::all()->keyBy('company_name');
        $users = User::all()->keyBy('email');

        // Every project below is fictional demonstration data.
        $projects = [
            [
                'client' => 'Kathmandu Codeworks', 'project_name' => 'Corporate Website Redesign',
                'description' => 'A complete redesign of the corporate website with a new content structure, responsive templates and a lightweight CMS for the marketing team.',
                'status' => 'in_progress', 'priority' => 'high', 'start' => -150, 'expected' => -105,
                'budget' => 185000, 'progress' => 65, 'assignee' => 'arya22013071@iimscollege.edu.np', 'created' => 150,
            ],
            [
                'client' => 'Gandaki Fintech', 'project_name' => 'Mobile Banking MVP',
                'description' => 'Discovery, user flows and a clickable mobile prototype for a savings and wallet product aimed at young professionals.',
                'status' => 'on_hold', 'priority' => 'urgent', 'start' => -143, 'expected' => -98,
                'budget' => 950000, 'progress' => 40, 'assignee' => 'rohan0@gmail.com', 'created' => 143,
            ],
            [
                'client' => 'Himalayan Bean Roasters', 'project_name' => 'Brand Identity Refresh',
                'description' => 'New visual identity, packaging system and brand guidelines for the specialty coffee range.',
                'status' => 'completed', 'priority' => 'medium', 'start' => -136, 'expected' => -91,
                'budget' => 120000, 'progress' => 100, 'assignee' => 'nitesh@gmail.com', 'created' => 136, 'completed' => 20,
            ],
            [
                'client' => 'Lumbini Textiles', 'project_name' => 'E-commerce Storefront',
                'description' => 'A catalogue and checkout experience for the handloom collection, integrated with local delivery partners.',
                'status' => 'planning', 'priority' => 'high', 'start' => -129, 'expected' => -84,
                'budget' => 420000, 'progress' => 10, 'assignee' => 'subinshakya12@gmail.com', 'created' => 129,
            ],
            [
                'client' => 'Pokhara Lakeside Resort', 'project_name' => 'Booking System Upgrade',
                'description' => 'Replacement of the legacy booking flow with a mobile-first reservation engine and payment reconciliation.',
                'status' => 'in_progress', 'priority' => 'high', 'start' => -122, 'expected' => -77,
                'budget' => 380000, 'progress' => 55, 'assignee' => 'rohan0@gmail.com', 'created' => 122,
            ],
            [
                'client' => 'Everest Trails Adventures', 'project_name' => 'SEO & Content Strategy',
                'description' => 'Technical SEO audit, keyword plan and a six-month content calendar for trekking packages.',
                'status' => 'in_progress', 'priority' => 'medium', 'start' => -115, 'expected' => -70,
                'budget' => 90000, 'progress' => 45, 'assignee' => 'nitesh@gmail.com', 'created' => 115,
            ],
            [
                'client' => 'Koshi Logistics', 'project_name' => 'Inventory Dashboard',
                'description' => 'A live inventory and dispatch dashboard that consolidates warehouse data from three branches.',
                'status' => 'in_progress', 'priority' => 'urgent', 'start' => -108, 'expected' => -63,
                'budget' => 560000, 'progress' => 70, 'assignee' => 'udesh@gmail.com', 'created' => 108,
            ],
            [
                'client' => 'Sunrise Media House', 'project_name' => 'Annual Report Design',
                'description' => 'Editorial design and print production for the annual report, including data visualisation pages.',
                'status' => 'completed', 'priority' => 'low', 'start' => -101, 'expected' => -56,
                'budget' => 85000, 'progress' => 100, 'assignee' => 'nitesh@gmail.com', 'created' => 101, 'completed' => 20,
            ],
            [
                'client' => 'Mero Health Labs', 'project_name' => 'Customer Portal',
                'description' => 'A secure patient portal for reports, appointments and prescription history.',
                'status' => 'in_progress', 'priority' => 'high', 'start' => -94, 'expected' => -49,
                'budget' => 720000, 'progress' => 35, 'assignee' => 'subinshakya12@gmail.com', 'created' => 94,
            ],
            [
                'client' => 'Terai Agro Supplies', 'project_name' => 'CRM Data Migration',
                'description' => 'Migration of dealer records from spreadsheets into the CRM, with duplicate checks and field mapping.',
                'status' => 'in_progress', 'priority' => 'medium', 'start' => -87, 'expected' => -42,
                'budget' => 250000, 'progress' => 20, 'assignee' => 'udesh@gmail.com', 'created' => 87,
            ],
            [
                'client' => 'Janakpur Craft House', 'project_name' => 'Social Media Campaign',
                'description' => 'Campaign concept, asset production and scheduling for the festive season collection.',
                'status' => 'planning', 'priority' => 'low', 'start' => -80, 'expected' => -35,
                'budget' => 65000, 'progress' => 5, 'assignee' => 'surajthakuri@gmail.com', 'created' => 80,
            ],
            [
                'client' => 'Kathmandu Codeworks', 'project_name' => 'Payment Gateway Integration',
                'description' => 'Integration of digital wallets and card payments into the existing checkout, including reconciliation reports.',
                'status' => 'in_progress', 'priority' => 'urgent', 'start' => -73, 'expected' => -28,
                'budget' => 210000, 'progress' => 80, 'assignee' => 'rohan0@gmail.com', 'created' => 73,
            ],
            [
                'client' => 'Bhaktapur Pottery Studio', 'project_name' => 'Product Catalogue Redesign',
                'description' => 'Photography direction and a new printed catalogue for the studio tableware collection.',
                'status' => 'in_progress', 'priority' => 'low', 'start' => -66, 'expected' => -21,
                'budget' => 75000, 'progress' => 40, 'assignee' => 'arya22013071@iimscollege.edu.np', 'created' => 66,
            ],
            [
                'client' => 'Himalayan Bean Roasters', 'project_name' => 'Loyalty Programme App',
                'description' => 'A points-based loyalty experience for repeat customers, with a staff redemption interface.',
                'status' => 'in_progress', 'priority' => 'medium', 'start' => -59, 'expected' => -14,
                'budget' => 340000, 'progress' => 25, 'assignee' => 'surajthakuri@gmail.com', 'created' => 59,
            ],
            [
                'client' => 'Gandaki Fintech', 'project_name' => 'Cloud Backup Setup',
                'description' => 'Assessment and rollout of encrypted off-site backups for critical financial records.',
                'status' => 'cancelled', 'priority' => 'medium', 'start' => -52, 'expected' => -7,
                'budget' => 110000, 'progress' => 10, 'assignee' => 'subinshakya12@gmail.com', 'created' => 52,
            ],
            [
                'client' => 'Koshi Logistics', 'project_name' => 'Warehouse Automation Study',
                'description' => 'Feasibility study for barcode scanning and automated stock counts across the warehouse network.',
                'status' => 'on_hold', 'priority' => 'high', 'start' => -45, 'expected' => 0,
                'budget' => 480000, 'progress' => 30, 'assignee' => 'udesh@gmail.com', 'created' => 45,
            ],
            [
                'client' => 'Pokhara Lakeside Resort', 'project_name' => 'Hotel Website Maintenance',
                'description' => 'Monthly maintenance retainer covering updates, performance checks and content changes.',
                'status' => 'completed', 'priority' => 'medium', 'start' => -38, 'expected' => -25,
                'budget' => 96000, 'progress' => 100, 'assignee' => 'nitesh@gmail.com', 'created' => 38, 'completed' => 20,
            ],
            [
                'client' => 'Everest Trails Adventures', 'project_name' => 'Marketing Automation',
                'description' => 'Email journeys and lead scoring connected to the existing enquiry form.',
                'status' => 'planning', 'priority' => 'high', 'start' => -31, 'expected' => 14,
                'budget' => 300000, 'progress' => 15, 'assignee' => 'arya22013071@iimscollege.edu.np', 'created' => 31,
            ],
            [
                'client' => 'Mero Health Labs', 'project_name' => 'Mobile App Phase 2',
                'description' => 'Second phase of the patient app with lab report downloads and family profiles.',
                'status' => 'in_progress', 'priority' => 'urgent', 'start' => -24, 'expected' => 21,
                'budget' => 880000, 'progress' => 60, 'assignee' => 'rohan0@gmail.com', 'created' => 24,
            ],
            [
                'client' => 'Sunrise Media House', 'project_name' => 'Corporate Video Production',
                'description' => 'A three-minute corporate film with interviews, motion graphics and subtitles.',
                'status' => 'not_started', 'priority' => 'medium', 'start' => -17, 'expected' => 28,
                'budget' => 150000, 'progress' => 0, 'assignee' => 'surajthakuri@gmail.com', 'created' => 17,
            ],
        ];

        foreach ($projects as $seed) {
            $client = $clients->get($seed['client']);

            if (! $client) {
                continue;
            }

            $assignee = $seed['assignee'] ? $users->get($seed['assignee'])?->id : null;

            $record = Project::updateOrCreate(
                ['client_id' => $client->id, 'project_name' => $seed['project_name']],
                [
                    'assigned_to' => $assignee,
                    'description' => $seed['description'],
                    'status' => $seed['status'],
                    'priority' => $seed['priority'],
                    'start_date' => now()->addDays($seed['start'])->toDateString(),
                    'expected_completion_date' => now()->addDays($seed['expected'])->toDateString(),
                    'budget' => $seed['budget'],
                    'progress' => $seed['progress'],
                    'completed_at' => isset($seed['completed']) ? now()->subDays($seed['completed']) : null,
                ]
            );

            $this->setCreatedAt($record, now()->subDays($seed['created']));
        }
    }
}
