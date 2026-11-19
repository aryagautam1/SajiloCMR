<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\Concerns\SetsCreatedAt;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    use SetsCreatedAt;

    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();

        // Every company and contact below is fictional demonstration data.
        $clients = [
            [
                'name' => 'Bishal Shrestha', 'company_name' => 'Himalayan Bean Roasters',
                'email' => 'bishal@himalayanbean.example', 'phone' => '+977 9801234501',
                'address' => 'Jhamsikhel, Lalitpur', 'status' => 'active',
                'notes' => 'Specialty coffee roaster. Prefers monthly progress calls and WhatsApp for quick updates.',
                'days_ago' => 340,
            ],
            [
                'name' => 'Sneha Gurung', 'company_name' => 'Kathmandu Codeworks',
                'email' => 'sneha@ktmcodeworks.example', 'phone' => '+977 9801234502',
                'address' => 'Baluwatar, Kathmandu', 'status' => 'active',
                'notes' => 'Software studio. Main point of contact for two active projects.',
                'days_ago' => 300,
            ],
            [
                'name' => 'Deepak Gurung', 'company_name' => 'Pokhara Lakeside Resort',
                'email' => 'deepak@lakesideresort.example', 'phone' => '+977 9801234503',
                'address' => 'Lakeside, Pokhara', 'status' => 'active',
                'notes' => 'Hospitality client. Booking season runs from October to March.',
                'days_ago' => 280,
            ],
            [
                'name' => 'Aarti Yadav', 'company_name' => 'Lumbini Textiles',
                'email' => 'aarti@lumbinitextiles.example', 'phone' => '+977 9801234504',
                'address' => 'Butwal, Rupandehi', 'status' => 'inactive',
                'notes' => 'Paused digital work for this quarter while the new production unit is completed.',
                'days_ago' => 260,
            ],
            [
                'name' => 'Tenzing Sherpa', 'company_name' => 'Everest Trails Adventures',
                'email' => 'tenzing@everesttrails.example', 'phone' => '+977 9801234505',
                'address' => 'Thamel, Kathmandu', 'status' => 'active',
                'notes' => 'Trekking operator. Peak enquiry period before the spring season.',
                'days_ago' => 230,
            ],
            [
                'name' => 'Priya Jha', 'company_name' => 'Janakpur Craft House',
                'email' => 'priya@janakpurcraft.example', 'phone' => '+977 9801234506',
                'address' => 'Janakpur, Madhesh', 'status' => 'prospect',
                'notes' => 'Referred by an existing client. Evaluating a festive campaign proposal.',
                'days_ago' => 90,
            ],
            [
                'name' => 'Ram Tharu', 'company_name' => 'Chitwan Organics',
                'email' => 'ram@chitwanorganics.example', 'phone' => '+977 9801234507',
                'address' => 'Bharatpur, Chitwan', 'status' => 'active',
                'notes' => 'Organic produce distributor. Interested in an ordering portal in the next phase.',
                'days_ago' => 200,
            ],
            [
                'name' => 'Maya Prajapati', 'company_name' => 'Bhaktapur Pottery Studio',
                'email' => 'maya@bhaktapurpottery.example', 'phone' => '+977 9801234508',
                'address' => 'Bhaktapur Durbar Square', 'status' => 'prospect',
                'notes' => 'Small studio with export ambitions. Catalogue project still at proposal stage.',
                'days_ago' => 45,
            ],
            [
                'name' => 'Nirajan Rai', 'company_name' => 'Koshi Logistics',
                'email' => 'nirajan@koshilogistics.example', 'phone' => '+977 9801234509',
                'address' => 'Biratnagar, Koshi', 'status' => 'active',
                'notes' => 'Regional logistics company. Data-heavy reporting requirements.',
                'days_ago' => 180,
            ],
            [
                'name' => 'Shreya Poudel', 'company_name' => 'Gandaki Fintech',
                'email' => 'shreya@gandakifintech.example', 'phone' => '+977 9801234510',
                'address' => 'Lakeside, Pokhara', 'status' => 'inactive',
                'notes' => 'Regulatory review is delaying the mobile banking pilot.',
                'days_ago' => 160,
            ],
            [
                'name' => 'Pemba Lama', 'company_name' => 'Sagarmatha Interiors',
                'email' => 'pemba@sagarmathainteriors.example', 'phone' => '+977 9801234511',
                'address' => 'Lazimpat, Kathmandu', 'status' => 'active',
                'notes' => 'Interior design studio. Strong interest in a portfolio website refresh.',
                'days_ago' => 130,
            ],
            [
                'name' => 'Sunil Yadav', 'company_name' => 'Terai Agro Supplies',
                'email' => 'sunil@teraiagro.example', 'phone' => '+977 9801234512',
                'address' => 'Birgunj, Parsa', 'status' => 'prospect',
                'notes' => 'Dealer network of 40+ shops. Needs a clean CRM before the next season.',
                'days_ago' => 30,
            ],
            [
                'name' => 'Anjali Sharma', 'company_name' => 'Mero Health Labs',
                'email' => 'anjali@merohealthlabs.example', 'phone' => '+977 9801234513',
                'address' => 'Pulchowk, Lalitpur', 'status' => 'active',
                'notes' => 'Diagnostic lab chain. Patient privacy and access control are critical.',
                'days_ago' => 120,
            ],
            [
                'name' => 'Karan Basnet', 'company_name' => 'Sunrise Media House',
                'email' => 'karan@sunrisemedia.example', 'phone' => '+977 9801234514',
                'address' => 'Putalisadak, Kathmandu', 'status' => 'inactive',
                'notes' => 'Media production house. Quiet period after their annual report delivery.',
                'days_ago' => 110,
            ],
        ];

        foreach ($clients as $client) {
            $record = Client::updateOrCreate(
                ['email' => $client['email']],
                [
                    'name' => $client['name'],
                    'company_name' => $client['company_name'],
                    'phone' => $client['phone'],
                    'address' => $client['address'],
                    'status' => $client['status'],
                    'notes' => $client['notes'],
                    'created_by' => $admin->id,
                ]
            );

            $this->setCreatedAt($record, now()->subDays($client['days_ago']));
        }
    }
}
