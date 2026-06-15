<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'new@example.com')
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in', 'user']]);
    }

    public function test_register_validation_fails_for_invalid_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'login@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'login@example.com')
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in', 'user']]);
    }

    public function test_login_returns_401_for_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'login@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('error', 'invalid_credentials');
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_returns_401_without_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_logout_invalidates_session(): void
    {
        User::factory()->create(['email' => 'logout@example.com']);
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'logout@example.com',
            'password' => 'password',
        ]);
        $token = $login->json('data.access_token');

        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('message', 'Successfully logged out.');
    }

    public function test_refresh_returns_new_token(): void
    {
        User::factory()->create(['email' => 'refresh@example.com']);
        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'refresh@example.com',
            'password' => 'password',
        ]);
        $token = $login->json('data.access_token');

        $this->postJson('/api/v1/auth/refresh', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']]);
    }
}
