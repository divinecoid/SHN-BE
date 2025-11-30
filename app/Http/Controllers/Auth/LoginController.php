<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
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
        } catch (\Illuminate\Database\ConnectionException $e) {
            Log::error('Database connection error during login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke database gagal. Silakan coba lagi nanti.',
                'error' => 'DATABASE_CONNECTION_ERROR',
                'data' => null,
            ], 503);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database query error during login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada database. Silakan coba lagi.',
                'error' => 'DATABASE_ERROR',
                'data' => null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error during login: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi.',
                'error' => 'UNEXPECTED_ERROR',
                'data' => null,
            ], 500);
        }
        
        try {
            // Cleanup expired tokens first
            $user->refreshTokens()->where('expires_at', '<', now())->delete();
            
            // Optional: Limit active tokens per user (e.g., max 5 devices)
            $maxTokens = 5;
            $activeTokens = $user->refreshTokens()->where('revoked', false)->count();
            if ($activeTokens >= $maxTokens) {
                // Remove oldest token
                $oldestToken = $user->refreshTokens()->where('revoked', false)->oldest()->first();
                if ($oldestToken) {
                    $oldestToken->delete();
                }
            }
            
            // Generate access token (short-lived)
            $accessToken = JWTAuth::fromUser($user);
            
            // Generate refresh token (UUID-based)
            $refreshToken = RefreshToken::createToken($user);
            
            $payload = JWTAuth::setToken($accessToken)->getPayload();
            Log::info('JWT Payload:', $payload->toArray());
            
            // Ambil role utama (gunakan role pertama jika multi-role)
            $roles = $user->roles()->select('roles.id', 'roles.name', 'roles.role_code')->get();
            $primaryRole = $roles->first();
            $roleCode = $primaryRole ? ($primaryRole->role_code ?? strtoupper(Str::slug($primaryRole->name, '_'))) : null;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'token' => $accessToken,
                'refresh_token' => $refreshToken->token,
                'token_type' => 'Bearer',
                'role_id' => $primaryRole->id ?? null,
                'role_name' => $primaryRole->name ?? null,
                'role_code' => $roleCode,
            ]);
        } catch (\Illuminate\Database\ConnectionException $e) {
            Log::error('Database connection error during token generation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke database gagal. Silakan coba lagi nanti.',
                'error' => 'DATABASE_CONNECTION_ERROR',
                'data' => null,
            ], 503);
        } catch (\Exception $e) {
            Log::error('Error during token generation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat token. Silakan coba lagi.',
                'error' => 'TOKEN_GENERATION_ERROR',
                'data' => null,
            ], 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            // Find refresh token in database
            $refreshToken = RefreshToken::findByToken($request->refresh_token);
            
            if (!$refreshToken || !$refreshToken->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token tidak valid atau expired',
                    'data' => null,
                ], 401);
            }

            // Get user from refresh token
            $user = $refreshToken->user;
            
            // Generate new access token
            $newToken = JWTAuth::fromUser($user);
            
            return response()->json([
                'success' => true,
                'message' => 'Token berhasil di-refresh',
                'token' => $newToken,
                'token_type' => 'Bearer',
            ]);

        } catch (\Illuminate\Database\ConnectionException $e) {
            Log::error('Database connection error during token refresh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke database gagal. Silakan coba lagi nanti.',
                'error' => 'DATABASE_CONNECTION_ERROR',
                'data' => null,
            ], 503);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database query error during token refresh: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada database. Silakan coba lagi.',
                'error' => 'DATABASE_ERROR',
                'data' => null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Refresh token tidak valid atau expired',
                'data' => null,
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            // Find and revoke the refresh token
            $refreshToken = RefreshToken::findByToken($request->refresh_token);
            
            if ($refreshToken) {
                $refreshToken->revoke();
                
                // Log logout activity
                Log::info('User logged out', [
                    'user_id' => $refreshToken->user_id,
                    'username' => $refreshToken->user->username,
                    'token_id' => $refreshToken->id,
                    'logout_time' => now()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil',
            ]);

        } catch (\Illuminate\Database\ConnectionException $e) {
            Log::error('Database connection error during logout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Koneksi ke database gagal. Silakan coba lagi nanti.',
                'error' => 'DATABASE_CONNECTION_ERROR',
                'data' => null,
            ], 503);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database query error during logout: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada database. Silakan coba lagi.',
                'error' => 'DATABASE_ERROR',
                'data' => null,
            ], 500);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat logout',
                'data' => null,
            ], 500);
        }
    }
}
