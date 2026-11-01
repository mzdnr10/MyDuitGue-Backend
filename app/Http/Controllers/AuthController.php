<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($validated);
        $user->role = 'user';
        $user->save();
        $token = $user->createToken('android')->plainTextToken;

        return $this->successResponse('Registration successful.', [
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function getToken(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            return $this->errorResponse(
                'AUTH_001',
                'The provided credentials are incorrect.',
                401
            );
        }

        return $this->successResponse('Token generated successfully.', [
            'user' => $user,
            'token' => $user->createToken('android')->plainTextToken,
        ]);
    }

    public function google(Request $request)
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        $clientId = config('services.google.client_id');

        if (!$clientId) {
            return $this->errorResponse(
                'GOOGLE_503',
                'Google authentication is not configured.',
                503
            );
        }

        $googleClient = new \Google\Client([
            'client_id' => config('services.google.client_id'),
        ]);

        // Verifikasi Google ID token
        $payload = $googleClient->verifyIdToken(
            $validated['id_token']
        );

        // Debug hasil verifikasi token
        // echo '<pre>';
        // print_r($payload);
        // exit();

        if (
            !$payload ||
            empty($payload['sub']) ||
            empty($payload['email'])
        ) {
            return $this->errorResponse(
                'GOOGLE_401',
                'The Google ID token is invalid.',
                401
            );
        }

        // Cari user berdasarkan Google ID atau email
        $user = User::where('google_id', $payload['sub'])
            ->orWhere('email', $payload['email'])
            ->first();

        if (!$user) {
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

        return $this->successResponse(
            'Google authentication successful.',
            [
                'user' => $user,
                'token' => $user->createToken('android')->plainTextToken,
            ]
        );
    }

    public function getClientToken(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        $client = ApiClient::where('client_id', $validated['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $client || ! Hash::check($validated['client_secret'], $client->client_secret)) {
            return $this->errorResponse('CLIENT_401', 'Invalid client credentials.', 401);
        }

        return $this->successResponse('Machine token generated successfully.', [
            'token' => $client->createToken('machine')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return $this->successResponse('Logout successful.');
    }

    public function me(Request $request)
    {
        if (! $request->user() instanceof User) {
            return $this->errorResponse('AUTH_403', 'User token required.', 403);
        }

        return $this->successResponse('Profile retrieved successfully.', [
            'user' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        if (! $request->user() instanceof User) {
            return $this->errorResponse('AUTH_403', 'User token required.', 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $request->user()->update($validated);

        return $this->successResponse('Profile updated successfully.', ['user' => $request->user()->fresh()]);
    }
}
