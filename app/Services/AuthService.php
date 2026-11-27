<?php

namespace App\Services;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $attributes): array
    {
        $user = User::create($attributes);
        $user->role = 'user';
        $user->save();

        return [
            'user' => $user,
            'token' => $user->createToken('android')->plainTextToken,
        ];
    }

    public function authenticateUser(array $credentials): ?array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        return [
            'user' => $user,
            'token' => $user->createToken('android')->plainTextToken,
        ];
    }

    public function authenticateGoogle(string $idToken): array
    {
        $clientId = config('services.google.client_id');

        if (! $clientId) {
            return ['error' => 'GOOGLE_503'];
        }

        $googleClient = new \Google\Client(['client_id' => $clientId]);
        $payload = $googleClient->verifyIdToken($idToken);

        if (! $payload || empty($payload['sub']) || empty($payload['email'])) {
            return ['error' => 'GOOGLE_401'];
        }

        $user = User::where('google_id', $payload['sub'])
            ->orWhere('email', $payload['email'])
            ->first();

        if (! $user) {
            $user = User::create([
                'name' => $payload['name'] ?? $payload['email'],
                'email' => $payload['email'],
                'google_id' => $payload['sub'],
                'avatar' => $payload['picture'] ?? null,
                'email_verified_at' => now(),
                'role' => 'user',
            ]);
        } else {
            $user->forceFill([
                'google_id' => $user->google_id ?: $payload['sub'],
                'avatar' => $user->avatar ?: ($payload['picture'] ?? null),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        }

        return [
            'user' => $user,
            'token' => $user->createToken('android')->plainTextToken,
        ];
    }

    public function authenticateClient(array $credentials): ?array
    {
        $client = ApiClient::where('client_id', $credentials['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $client || ! Hash::check($credentials['client_secret'], $client->client_secret)) {
            return null;
        }

        return [
            'token' => $client->createToken('machine')->plainTextToken,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User|ApiClient $principal): void
    {
        $principal->currentAccessToken()?->delete();
    }

    public function updateProfile(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh();
    }
}
