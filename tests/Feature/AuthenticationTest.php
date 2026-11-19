<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_and_api_is_protected(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/clients')->assertRedirect('/login');
        $this->get('/projects')->assertRedirect('/login');
        $this->getJson('/api/clients')->assertStatus(401);
        $this->getJson('/api/dashboard/statistics')->assertStatus(401);
    }

    public function test_login_page_renders_for_guests(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Sajilo')
            ->assertSee('Sign in');
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_user_can_sign_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_user_cannot_sign_in_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'admin@example.test',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_signed_in_user_can_sign_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_api_login_returns_a_bearer_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'Password123',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'admin@example.test')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'name', 'email', 'role', 'role_label', 'initials'],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame('phpunit', $user->tokens()->first()->name);
    }

    public function test_api_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'not-the-password',
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_api_token_authenticates_and_logout_revokes_it(): void
    {
        User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ]);

        $token = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'Password123',
        ])->json('data.token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)
            ->getJson('/api/auth/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@example.test');

        $this->withHeaders($headers)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // The framework caches the resolved guard user between requests in tests.
        $this->app['auth']->forgetGuards();

        $this->withHeaders($headers)
            ->getJson('/api/auth/user')
            ->assertStatus(401);
    }
}
