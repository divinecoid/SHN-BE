<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $roles = null): Response
    {
        // Ambil user dari JWT
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired',
                'data' => null,
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid',
                'data' => null,
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau tidak ada',
                'data' => null,
            ], 401);
        }
        // Jika ada parameter roles, cek role user
        if ($roles) {
            $roleArray = explode(',', $roles);
            $hasRole = false;
            foreach ($roleArray as $role) {
                if ($user->hasRole(trim($role))) {
                    $hasRole = true;
                    break;
                }
            }
            if (!$hasRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses hanya untuk role: ' . $roles,
                    'data' => null,
                ], 403);
            }
        }
        // Set user ke request agar bisa diakses controller
        $request->setUserResolver(function () use ($user) {
            return $user;
        });
        return $next($request);
    }
}
