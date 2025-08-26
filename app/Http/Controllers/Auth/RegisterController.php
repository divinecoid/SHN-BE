<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Role;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        // Create the user
        $user = User::create([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // Assign role from request
        $user->roles()->attach($validated['role_id']);

        // Generate access token (short-lived)
        $accessToken = JWTAuth::fromUser($user);
        
        // Generate refresh token (UUID-based)
        $refreshToken = RefreshToken::createToken($user);
        
        $payload = JWTAuth::setToken($accessToken)->getPayload();

        // Log ke console Laravel
        Log::info('JWT Payload:', $payload->toArray());
        // Return success response with token
        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'roles' => $user->roles->pluck('name')
            ],
            'token' => $accessToken,
            'refresh_token' => $refreshToken->token,
            'token_type' => 'Bearer'
        ], 201);
    }
}
