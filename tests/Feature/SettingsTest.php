<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_their_profile(): void
    {
        $user = User::factory()->staff()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/settings/profile', [
            'name' => 'Prakriti Adhikari',
            'email' => 'prakriti.new@sajilocrm.test',
            'phone' => '+977 9801000202',
            'job_title' => 'Project Coordinator',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Prakriti Adhikari')
            ->assertJsonPath('data.email', 'prakriti.new@sajilocrm.test');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'prakriti.new@sajilocrm.test',
        ]);
    }

    public function test_profile_validation_rejects_duplicate_email_and_invalid_phone(): void
    {
        User::factory()->create(['email' => 'taken@sajilocrm.test']);
        $user = User::factory()->staff()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/settings/profile', [
            'name' => $user->name,
            'email' => 'taken@sajilocrm.test',
            'phone' => 'call me maybe',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->staff()->create(['password' => 'Password123']);
        Sanctum::actingAs($user);

        $this->putJson('/api/settings/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('Password123', $user->fresh()->password));
    }

    public function test_password_change_revokes_other_api_tokens_but_keeps_the_current_one(): void
    {
        $user = User::factory()->staff()->create(['password' => 'Password123']);
        $currentToken = $user->createToken('current')->plainTextToken;
        $otherToken = $user->createToken('other')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->putJson('/api/settings/password', [
                'current_password' => 'Password123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
        $this->assertSame('current', $user->tokens()->first()->name);

        $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->getJson('/api/auth/user')
            ->assertOk();

        // The framework caches the resolved guard user between requests in tests.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$otherToken)
            ->getJson('/api/auth/user')
            ->assertStatus(401);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'NewPassword456',
        ])->assertOk();
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->staff()->create(['password' => 'Password123']);
        Sanctum::actingAs($user);

        $this->putJson('/api/settings/password', [
            'current_password' => 'Password123',
            'password' => 'NewPassword456',
            'password_confirmation' => 'Different789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
