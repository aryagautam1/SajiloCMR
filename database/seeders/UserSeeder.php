<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Sajilo@2026';

    public function run(): void
    {
        $users = [
            [
                'name' => 'Arya Gautam',
                'email' => 'arya22013071@iimscollege.edu.np',
                'role' => User::ROLE_ADMIN,
                'phone' => '+977 9801000201',
                'job_title' => 'Frontend Developer & Backend Support (intern)',
            ],
            [
                'name' => 'Nitesh Panta',
                'email' => 'nitesh@gmail.com',
                'role' => User::ROLE_STAFF,
                'phone' => '+977 9801000202',
                'job_title' => 'Project Manager',
            ],
            [
                'name' => 'Rohan Karki',
                'email' => 'rohan0@gmail.com',
                'role' => User::ROLE_STAFF,
                'phone' => '+977 9801000203',
                'job_title' => 'Full Stack Developer',
            ],
            [
                'name' => 'Udesh Shrestha',
                'email' => 'udesh@gmail.com',
                'role' => User::ROLE_STAFF,
                'phone' => '+977 9801000204',
                'job_title' => 'Data Analyst(intern)',
            ],
            [
                'name' => 'Suraj Chand Thakuri',
                'email' => 'surajthakuri@gmail.com',
                'role' => User::ROLE_STAFF,
                'phone' => '+977 9801000205',
                'job_title' => 'CEO',
            ],
            [
                'name' => 'Subin Shakya',
                'email' => 'subinshakya12@gmail.com',
                'role' => User::ROLE_STAFF,
                'phone' => '9800010101',
                'job_title' => 'QA Analyst',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                array_merge($user, ['password' => self::DEMO_PASSWORD])
            );
        }
    }
}
