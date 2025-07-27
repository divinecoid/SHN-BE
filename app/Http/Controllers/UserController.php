<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ApiFilterTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = User::with('roles');
        $query = $this->applyFilter($query, $request, ['name', 'username', 'email']);
        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'username' => $item->username,
                'email' => $item->email,
                'roles' => $item->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name
                    ];
                })
            ];
        });
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::with('roles')->find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name
                ];
            })
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'username' => 'sometimes|required|string|unique:users,username,' . $user->id,
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $updateData = $request->only(['name', 'username', 'email']);
        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);
        return $this->successResponse($user, 'User berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }
        $user->delete();
        return $this->successResponse(null, 'User berhasil dihapus');
    }

    /**
     * Soft delete a user
     */
    public function softDelete($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }
        $user->delete(); // This will soft delete
        return $this->successResponse(null, 'User berhasil di-soft delete');
    }

    /**
     * Restore soft deleted user
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan atau tidak soft deleted', 404);
        }
        $user->restore();
        return $this->successResponse($user, 'User berhasil di-restore');
    }

    /**
     * Force delete user (permanent delete)
     */
    public function forceDelete($id)
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return $this->errorResponse('User tidak ditemukan', 404);
        }
        $user->forceDelete();
        return $this->successResponse(null, 'User berhasil di-force delete');
    }

    /**
     * Get all users including soft deleted
     */
    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = User::withTrashed()->with('roles');
        $query = $this->applyFilter($query, $request, ['name', 'username', 'email']);
        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'username' => $item->username,
                'email' => $item->email,
                'deleted_at' => $item->deleted_at,
                'roles' => $item->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name
                    ];
                })
            ];
        });
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get only soft deleted users
     */
    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = User::onlyTrashed()->with('roles');
        $query = $this->applyFilter($query, $request, ['name', 'username', 'email']);
        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'username' => $item->username,
                'email' => $item->email,
                'deleted_at' => $item->deleted_at,
                'roles' => $item->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name
                    ];
                })
            ];
        });
        return response()->json($this->paginateResponse($data, $items));
    }
} 