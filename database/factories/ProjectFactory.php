<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['planning', 'not_started', 'in_progress', 'in_progress', 'on_hold', 'completed']);
        $progress = match ($status) {
            'planning' => fake()->numberBetween(5, 15),
            'not_started' => 0,
            'in_progress' => fake()->numberBetween(20, 85),
            'on_hold' => fake()->numberBetween(25, 55),
            'completed' => 100,
            default => 0,
        };

        $start = fake()->dateTimeBetween('-8 months', 'now');

        return [
            'client_id' => Client::factory(),
            'assigned_to' => User::factory()->staff(),
            'project_name' => fake()->randomElement([
                'Website Redesign', 'Mobile App MVP', 'Brand Identity Refresh',
                'CRM Integration', 'E-commerce Platform', 'SEO Strategy',
            ]).' '.fake()->unique()->numerify('##'),
            'description' => fake()->paragraph(),
            'status' => $status,
            'priority' => fake()->randomElement(['low', 'medium', 'medium', 'high', 'urgent']),
            'start_date' => $start->format('Y-m-d'),
            'expected_completion_date' => fake()->dateTimeBetween($start, '+8 months')->format('Y-m-d'),
            'budget' => fake()->numberBetween(50000, 900000),
            'progress' => $progress,
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween($start, 'now') : null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now()->subDays(10),
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }
}
