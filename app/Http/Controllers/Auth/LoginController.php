<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('username', $request->username)->first();
        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau password salah',
                'data' => null,
            ], 401);
        }
        $token = JWTAuth::fromUser($user);
        $payload = JWTAuth::setToken($token)->getPayload();
        Log::info('JWT Payload:', $payload->toArray());
        
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        try {
            // Set the refresh token
            JWTAuth::setToken($request->refresh_token);
            
            // Get the user from the token
            $user = JWTAuth::authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token tidak valid',
                    'data' => null,
                ], 401);
            }

            // Generate new token
            $newToken = JWTAuth::fromUser($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Token berhasil di-refresh',
                'token' => $newToken,
                'token_type' => 'Bearer',
            ]);

        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Refresh token tidak valid atau expired',
                'data' => null,
            ], 401);
        }
    }
}
