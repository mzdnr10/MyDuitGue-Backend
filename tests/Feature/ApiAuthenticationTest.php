<?php

namespace Tests\Feature;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_view_profile_and_logout(): void
    {
        $credentials = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $registerResponse = $this->postJson('/api/register', $credentials);

        $registerResponse->assertCreated()
            ->assertJsonStructure([
                'responseCode',
                'responseMessage',
                'data' => ['user', 'token'],
            ])
            ->assertJsonPath('data.user.role', 'user');

        $loginResponse = $this->postJson('/api/gettoken', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ]);

        $loginResponse->assertOk()->assertJsonStructure([
            'responseCode',
            'responseMessage',
            'data' => ['user', 'token'],
        ]);
        $token = $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', $credentials['email']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.user.email', $credentials['email']);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => (int) explode('|', $token, 2)[0],
        ]);

        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_machine_client_can_get_a_sanctum_token(): void
    {
        ApiClient::create([
            'name' => 'Test machine client',
            'client_id' => 'test-client',
            'client_secret' => Hash::make('test-secret'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/gettoken/client', [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
        ])->assertOk()
            ->assertJsonStructure([
                'responseCode',
                'responseMessage',
                'data' => ['token', 'token_type'],
            ]);

        $this->assertDatabaseMissing('api_clients', ['client_secret' => 'test-secret']);
        $this->assertArrayNotHasKey('client_secret', $response->json('data'));

        $this->withHeader('Authorization', 'Bearer ' . $response->json('data.token'))
            ->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('responseCode', 'AUTH_403');
    }

    public function test_me_rejects_invalid_and_expired_tokens(): void
    {
        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/me')
            ->assertUnauthorized();

        $user = User::factory()->create();
        $newToken = $user->createToken('android');
        $token = $newToken->accessToken;
        $token->forceFill([
            'created_at' => now()->subMinutes((int) config('sanctum.expiration') + 1),
        ])->save();

        Auth::forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $newToken->plainTextToken)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_authentication_errors_use_the_standard_response_format(): void
    {
        $this->postJson('/api/gettoken', [
            'email' => 'missing@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJson([
                'responseCode' => 'AUTH_001',
                'responseMessage' => 'The provided credentials are incorrect.',
                'data' => null,
            ]);

        $this->postJson('/api/register', [])
            ->assertUnprocessable()
            ->assertJsonPath('responseCode', 'VALIDATION_422')
            ->assertJsonStructure([
                'responseCode',
                'responseMessage',
                'data' => ['errors'],
            ]);
    }
}
