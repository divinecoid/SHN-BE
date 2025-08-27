<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\ApiFilterTrait;

class RoleController extends Controller
{
    use ApiFilterTrait;

    /**
     * Get all roles with pagination and filtering
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = Role::query();
        $query = $this->applyFilter($query, $request, ['name', 'description']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Create a new role
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $role = Role::create($request->only(['name', 'description']));
        return $this->successResponse($role, 'Role berhasil ditambahkan');
    }

    /**
     * Get a specific role
     */
    public function show($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }
        return $this->successResponse($role);
    }

    /**
     * Update a role
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $role->id . '|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $role->update($request->only(['name', 'description']));
        return $this->successResponse($role, 'Role berhasil diperbarui');
    }

    /**
     * Delete a role
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }

        // Check if role is being used by any user
        if ($role->users()->count() > 0) {
            return $this->errorResponse('Role tidak dapat dihapus karena masih digunakan oleh user', 422);
        }

        $role->delete();
        return $this->successResponse(null, 'Role berhasil dihapus');
    }
}
