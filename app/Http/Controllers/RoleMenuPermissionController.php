<?php

namespace App\Http\Controllers;

use App\Models\RoleMenuPermission;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\ApiFilterTrait;

class RoleMenuPermissionController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = RoleMenuPermission::with(['role', 'menu', 'permission']);
        $query = $this->applyFilter($query, $request, ['role_id', 'menu_id', 'permission_id']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'menu_id' => 'required|integer|exists:ref_menu,id',
            'permission_id' => 'required|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Check if the combination already exists
        $existing = RoleMenuPermission::where([
            'role_id' => $request->role_id,
            'menu_id' => $request->menu_id,
            'permission_id' => $request->permission_id,
        ])->first();

        if ($existing) {
            return $this->errorResponse('Role-menu-permission combination already exists', 422);
        }

        $data = RoleMenuPermission::create($request->only(['role_id', 'menu_id', 'permission_id']));
        $data->load(['role', 'menu', 'permission']);
        
        return $this->successResponse($data, 'Role-menu-permission mapping berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = RoleMenuPermission::with(['role', 'menu', 'permission'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = RoleMenuPermission::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'menu_id' => 'required|integer|exists:ref_menu,id',
            'permission_id' => 'required|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Check if the combination already exists (excluding current record)
        $existing = RoleMenuPermission::where([
            'role_id' => $request->role_id,
            'menu_id' => $request->menu_id,
            'permission_id' => $request->permission_id,
        ])->where('id', '!=', $id)->first();

        if ($existing) {
            return $this->errorResponse('Role-menu-permission combination already exists', 422);
        }

        $data->update($request->only(['role_id', 'menu_id', 'permission_id']));
        $data->load(['role', 'menu', 'permission']);
        
        return $this->successResponse($data, 'Role-menu-permission mapping berhasil diperbarui');
    }

    public function destroy($id)
    {
        $data = RoleMenuPermission::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Role-menu-permission mapping berhasil dihapus');
    }

    /**
     * Get role-menu-permission mappings by role ID
     */
    public function getByRole($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }

        $mappings = RoleMenuPermission::with(['menu', 'permission'])
            ->where('role_id', $roleId)
            ->get();

        return $this->successResponse([
            'role' => $role,
            'mappings' => $mappings
        ], 'Data role-menu-permission mapping berhasil diambil');
    }

    /**
     * Get role-menu-permission mappings by menu ID
     */
    public function getByMenu($menuId)
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            return $this->errorResponse('Menu tidak ditemukan', 404);
        }

        $mappings = RoleMenuPermission::with(['role', 'permission'])
            ->where('menu_id', $menuId)
            ->get();

        return $this->successResponse([
            'menu' => $menu,
            'mappings' => $mappings
        ], 'Data role-menu-permission mapping berhasil diambil');
    }

    /**
     * Bulk create role-menu-permission mappings
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'mappings' => 'required|array',
            'mappings.*.menu_id' => 'required|integer|exists:ref_menu,id',
            'mappings.*.permission_id' => 'required|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $roleId = $request->role_id;
        $mappings = $request->mappings;
        $created = [];
        $errors = [];

        foreach ($mappings as $mapping) {
            // Check if the combination already exists
            $existing = RoleMenuPermission::where([
                'role_id' => $roleId,
                'menu_id' => $mapping['menu_id'],
                'permission_id' => $mapping['permission_id'],
            ])->first();

            if ($existing) {
                $errors[] = "Mapping untuk role_id: {$roleId}, menu_id: {$mapping['menu_id']}, permission_id: {$mapping['permission_id']} sudah ada";
                continue;
            }

            $data = RoleMenuPermission::create([
                'role_id' => $roleId,
                'menu_id' => $mapping['menu_id'],
                'permission_id' => $mapping['permission_id'],
            ]);
            $data->load(['role', 'menu', 'permission']);
            $created[] = $data;
        }

        return $this->successResponse([
            'created' => $created,
            'errors' => $errors
        ], 'Bulk role-menu-permission mapping berhasil diproses');
    }

    /**
     * Delete all mappings for a specific role
     */
    public function deleteByRole($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }

        $deleted = RoleMenuPermission::where('role_id', $roleId)->delete();
        
        return $this->successResponse(null, "Berhasil menghapus {$deleted} role-menu-permission mappings untuk role: {$role->name}");
    }

    /**
     * Delete all mappings for a specific menu
     */
    public function deleteByMenu($menuId)
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            return $this->errorResponse('Menu tidak ditemukan', 404);
        }

        $deleted = RoleMenuPermission::where('menu_id', $menuId)->delete();
        
        return $this->successResponse(null, "Berhasil menghapus {$deleted} role-menu-permission mappings untuk menu: {$menu->nama_menu}");
    }
}
