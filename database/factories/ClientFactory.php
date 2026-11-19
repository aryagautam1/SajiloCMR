<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = fake()->company();

        return [
            'name' => fake()->name(),
            'company_name' => $company,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+977 98########'),
            'address' => fake()->city().', Nepal',
            'status' => fake()->randomElement(['active', 'active', 'inactive', 'prospect']),
            'notes' => fake()->optional()->sentence(12),
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inactive']);
    }

    public function prospect(): static
    {
        return $this->state(fn () => ['status' => 'prospect']);
    }
}
