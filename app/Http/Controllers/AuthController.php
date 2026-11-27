<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        return $this->successResponse(
            'Registration successful.',
            $this->authService->register($validated),
            201
        );
    }

    public function getToken(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $result = $this->authService->authenticateUser($validated);

        if ($result === null) {
            return $this->errorResponse(
                'AUTH_001',
                'The provided credentials are incorrect.',
                401
            );
        }

        return $this->successResponse('Token generated successfully.', $result);
    }

    public function google(Request $request)
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
        ]);
        $result = $this->authService->authenticateGoogle($validated['id_token']);

        if (($result['error'] ?? null) === 'GOOGLE_503') {
            return $this->errorResponse(
                'GOOGLE_503',
                'Google authentication is not configured.',
                503
            );
        }

        if (($result['error'] ?? null) === 'GOOGLE_401') {
            return $this->errorResponse(
                'GOOGLE_401',
                'The Google ID token is invalid.',
                401
            );
        }

        return $this->successResponse('Google authentication successful.', $result);
    }

    public function getClientToken(Request $request)
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);
        $result = $this->authService->authenticateClient($validated);

        if ($result === null) {
            return $this->errorResponse('CLIENT_401', 'Invalid client credentials.', 401);
        }

        return $this->successResponse('Machine token generated successfully.', $result);
    }

    public function logout(Request $request)
    {
        $principal = $request->user();
        if ($principal instanceof User || $principal instanceof ApiClient) {
            $this->authService->logout($principal);
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

        return $this->successResponse('Profile updated successfully.', [
            'user' => $this->authService->updateProfile($request->user(), $validated),
        ]);
    }
}
