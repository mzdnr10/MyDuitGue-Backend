<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
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
}
