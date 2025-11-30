<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleMenuPermission;
use App\Models\MenuPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
            'mappings' => 'nullable|array',
            'mappings.*.menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
            'mappings.*.menu_id' => 'nullable|integer|exists:ref_menu,id',
            'mappings.*.permission_id' => 'nullable|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $role = Role::create($request->only(['name', 'description']));
            if ($request->filled('mappings')) {
                $targetIds = [];
                foreach ($request->mappings as $mapping) {
                    $mpId = $mapping['menu_menu_permission_id'] ?? null;
                    if (!$mpId) {
                        if (!isset($mapping['menu_id']) || !isset($mapping['permission_id'])) {
                            DB::rollBack();
                            return $this->errorResponse('mapping harus berisi menu_menu_permission_id atau pasangan menu_id + permission_id', 422);
                        }
                        $mp = MenuPermission::firstOrCreate([
                            'menu_id' => $mapping['menu_id'],
                            'permission_id' => $mapping['permission_id'],
                        ]);
                        $mpId = $mp->id;
                    }
                    $targetIds[] = (int)$mpId;
                }
                foreach (array_unique($targetIds) as $mpId) {
                    RoleMenuPermission::firstOrCreate([
                        'role_id' => $role->id,
                        'menu_menu_permission_id' => $mpId,
                    ]);
                }
            }
            DB::commit();
            return $this->successResponse($role, 'Role berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menambahkan role: ' . $e->getMessage(), 500);
        }
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
            'mappings' => 'nullable|array',
            'mappings.*.menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
            'mappings.*.menu_id' => 'nullable|integer|exists:ref_menu,id',
            'mappings.*.permission_id' => 'nullable|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $role->update($request->only(['name', 'description']));
            if ($request->filled('mappings')) {
                $targetIds = [];
                foreach ($request->mappings as $mapping) {
                    $mpId = $mapping['menu_menu_permission_id'] ?? null;
                    if (!$mpId) {
                        if (!isset($mapping['menu_id']) || !isset($mapping['permission_id'])) {
                            DB::rollBack();
                            return $this->errorResponse('mapping harus berisi menu_menu_permission_id atau pasangan menu_id + permission_id', 422);
                        }
                        $mp = MenuPermission::firstOrCreate([
                            'menu_id' => $mapping['menu_id'],
                            'permission_id' => $mapping['permission_id'],
                        ]);
                        $mpId = $mp->id;
                    }
                    $targetIds[] = (int)$mpId;
                }
                $existing = RoleMenuPermission::where('role_id', $role->id)->pluck('menu_menu_permission_id')->map(fn($v) => (int)$v)->all();
                $toDelete = array_values(array_diff($existing, $targetIds));
                $toAdd = array_values(array_diff($targetIds, $existing));
                if (!empty($toDelete)) {
                    RoleMenuPermission::where('role_id', $role->id)
                        ->whereIn('menu_menu_permission_id', $toDelete)
                        ->delete();
                }
                foreach ($toAdd as $mpId) {
                    RoleMenuPermission::firstOrCreate([
                        'role_id' => $role->id,
                        'menu_menu_permission_id' => $mpId,
                    ]);
                }
            }
            DB::commit();
            return $this->successResponse($role, 'Role berhasil diperbarui');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal memperbarui role: ' . $e->getMessage(), 500);
        }
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
