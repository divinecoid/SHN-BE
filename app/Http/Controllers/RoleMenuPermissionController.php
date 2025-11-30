<?php

namespace App\Http\Controllers;

use App\Models\RoleMenuPermission;
use App\Models\Role;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiFilterTrait;
use Illuminate\Support\Str;

class RoleMenuPermissionController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = RoleMenuPermission::with(['role', 'menuPermission.menu', 'menuPermission.permission']);
        $query = $this->applyFilter($query, $request, ['role_id', 'menu_menu_permission_id']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
            'menu_id' => 'nullable|integer|exists:ref_menu,id',
            'permission_id' => 'nullable|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Resolve menu_permission_id if not provided
        $menuPermissionId = $request->menu_menu_permission_id;
        if (!$menuPermissionId) {
            if (!$request->filled('menu_id') || !$request->filled('permission_id')) {
                return $this->errorResponse('menu_permission_id atau pasangan menu_id + permission_id wajib diisi', 422);
            }
            $mp = \App\Models\MenuPermission::firstOrCreate(
                ['menu_id' => $request->menu_id, 'permission_id' => $request->permission_id]
            );
            $menuPermissionId = $mp->id;
        }

        // Check if the combination already exists
        $existing = RoleMenuPermission::where([
            'role_id' => $request->role_id,
            'menu_menu_permission_id' => $menuPermissionId,
        ])->first();

        if ($existing) {
            return $this->errorResponse('Role-menu-permission combination already exists', 422);
        }

        $data = RoleMenuPermission::create([
            'role_id' => $request->role_id,
            'menu_menu_permission_id' => $menuPermissionId,
        ]);
        $data->load(['role', 'menuPermission.menu', 'menuPermission.permission']);
        
        return $this->successResponse($data, 'Role-menu-permission mapping berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = RoleMenuPermission::with(['role', 'menuPermission.menu', 'menuPermission.permission'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        // Mode sinkronisasi penuh jika ada 'mappings'
        if ($request->filled('mappings')) {
            $validator = Validator::make($request->all(), [
                'role_id' => 'required|integer|exists:roles,id',
                'mappings' => 'required|array',
                'mappings.*.menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
                'mappings.*.menu_id' => 'nullable|integer|exists:ref_menu,id',
                'mappings.*.permission_id' => 'nullable|integer|exists:ref_permission,id',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }
            DB::beginTransaction();
            try {
                $roleId = (int)$request->role_id;
                $targetIds = [];
                foreach ($request->mappings as $mapping) {
                    $mpId = $mapping['menu_menu_permission_id'] ?? null;
                    if (!$mpId) {
                        if (!isset($mapping['menu_id']) || !isset($mapping['permission_id'])) {
                            DB::rollBack();
                            return $this->errorResponse('mapping harus berisi menu_menu_permission_id atau pasangan menu_id + permission_id', 422);
                        }
                        $mp = \App\Models\MenuPermission::firstOrCreate([
                            'menu_id' => $mapping['menu_id'],
                            'permission_id' => $mapping['permission_id'],
                        ]);
                        $mpId = $mp->id;
                    }
                    $targetIds[] = (int)$mpId;
                }
                $existing = RoleMenuPermission::where('role_id', $roleId)->pluck('menu_menu_permission_id')->map(fn($v) => (int)$v)->all();
                $toDelete = array_values(array_diff($existing, $targetIds));
                $toAdd = array_values(array_diff($targetIds, $existing));
                $deleted = 0;
                if (!empty($toDelete)) {
                    $deleted = RoleMenuPermission::where('role_id', $roleId)
                        ->whereIn('menu_menu_permission_id', $toDelete)
                        ->delete();
                }
                $created = [];
                foreach ($toAdd as $mpId) {
                    $row = RoleMenuPermission::create([
                        'role_id' => $roleId,
                        'menu_menu_permission_id' => $mpId,
                    ]);
                    $row->load(['menuPermission.menu', 'menuPermission.permission']);
                    $created[] = $row;
                }
                DB::commit();
                return $this->successResponse([
                    'deleted_count' => $deleted,
                    'created_count' => count($created),
                    'created' => $created,
                ], 'Role-menu-permission berhasil diupdate (sinkronisasi)');
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse('Gagal update (sync): ' . $e->getMessage(), 500);
            }
        }

        // Mode update satu baris (legacy)
        $data = RoleMenuPermission::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
            'menu_id' => 'nullable|integer|exists:ref_menu,id',
            'permission_id' => 'nullable|integer|exists:ref_permission,id',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $menuPermissionId = $request->menu_menu_permission_id;
        if (!$menuPermissionId) {
            if (!$request->filled('menu_id') || !$request->filled('permission_id')) {
                return $this->errorResponse('menu_permission_id atau pasangan menu_id + permission_id wajib diisi', 422);
            }
            $mp = \App\Models\MenuPermission::firstOrCreate(
                ['menu_id' => $request->menu_id, 'permission_id' => $request->permission_id]
            );
            $menuPermissionId = $mp->id;
        }
        $existing = RoleMenuPermission::where([
            'role_id' => $request->role_id,
            'menu_menu_permission_id' => $menuPermissionId,
        ])->where('id', '!=', $id)->first();
        if ($existing) {
            return $this->errorResponse('Role-menu-permission combination already exists', 422);
        }
        $data->update([
            'role_id' => $request->role_id,
            'menu_menu_permission_id' => $menuPermissionId,
        ]);
        $data->load(['role', 'menuPermission.menu', 'menuPermission.permission']);
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

        $mappings = RoleMenuPermission::with(['menuPermission.menu', 'menuPermission.permission'])
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

        $mappings = RoleMenuPermission::with(['role', 'menuPermission.permission'])
            ->whereHas('menuPermission', function($q) use ($menuId) {
                $q->where('menu_id', $menuId);
            })
            ->get();

        return $this->successResponse([
            'menu' => $menu,
            'mappings' => $mappings
        ], 'Data role-menu-permission mapping berhasil diambil');
    }

    /**
     * Get compact role permissions by role ID
     * Format dikembalikan ringkas untuk validasi di FE:
     * {
     *   role_id: 1,
     *   role_name: "Admin",
     *   map: { "Sales Order": ["Create","Read"], "Laporan": ["Read"] }
     * }
     */
    public function compactByRole($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }

        $mappings = RoleMenuPermission::with(['menuPermission.menu', 'menuPermission.permission'])
            ->where('role_id', $roleId)
            ->get();

        $map = [];
        foreach ($mappings as $m) {
            $menu = $m->menuPermission?->menu;
            $perm = $m->menuPermission?->permission;
            if (!$menu || !$perm) continue;
            $menuName = $menu->nama_menu;
            if (!isset($map[$menuName])) {
                $map[$menuName] = [];
            }
            if (!in_array($perm->nama_permission, $map[$menuName], true)) {
                $map[$menuName][] = $perm->nama_permission;
            }
        }

        return $this->successResponse([
            'role_id' => $role->id,
            'role_name' => $role->name,
            'map' => $map,
        ], 'Role permission (compact) berhasil diambil');
    }

    public function compactByRoleName($roleName)
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }
        $mappings = RoleMenuPermission::with(['menuPermission.menu', 'menuPermission.permission'])
            ->where('role_id', $role->id)
            ->get();
        $map = [];
        foreach ($mappings as $m) {
            $menu = $m->menuPermission?->menu;
            $perm = $m->menuPermission?->permission;
            if (!$menu || !$perm) continue;
            $menuName = $menu->nama_menu;
            if (!isset($map[$menuName])) {
                $map[$menuName] = [];
            }
            if (!in_array($perm->nama_permission, $map[$menuName], true)) {
                $map[$menuName][] = $perm->nama_permission;
            }
        }
        return $this->successResponse([
            'role_id' => $role->id,
            'role_name' => $role->name,
            'map' => $map,
        ], 'Role permission (compact) berhasil diambil');
    }

    /**
     * Bulk create role-menu-permission mappings
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'mappings' => 'required|array',
            'mappings.*.menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
            'mappings.*.menu_id' => 'nullable|integer|exists:ref_menu,id',
            'mappings.*.permission_id' => 'nullable|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $roleId = $request->role_id;
        $mappings = $request->mappings;
        $created = [];
        $errors = [];

        foreach ($mappings as $mapping) {
            $mpId = $mapping['menu_menu_permission_id'] ?? null;
            if (!$mpId) {
                if (!isset($mapping['menu_id']) || !isset($mapping['permission_id'])) {
                    $errors[] = 'mapping harus berisi menu_permission_id atau pasangan menu_id + permission_id';
                    continue;
                }
                $mp = \App\Models\MenuPermission::firstOrCreate([
                    'menu_id' => $mapping['menu_id'],
                    'permission_id' => $mapping['permission_id'],
                ]);
                $mpId = $mp->id;
            }

            // Check if the combination already exists
            $existing = RoleMenuPermission::where([
                'role_id' => $roleId,
                'menu_menu_permission_id' => $mpId,
            ])->first();

            if ($existing) {
                $errors[] = "Mapping untuk role_id: {$roleId}, menu_id: {$mapping['menu_id']}, permission_id: {$mapping['permission_id']} sudah ada";
                continue;
            }

            $data = RoleMenuPermission::create([
                'role_id' => $roleId,
                'menu_menu_permission_id' => $mpId,
            ]);
            $data->load(['role', 'menuPermission.menu', 'menuPermission.permission']);
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

        $deleted = RoleMenuPermission::whereHas('menuPermission', function($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })->delete();
        
        return $this->successResponse(null, "Berhasil menghapus {$deleted} role-menu-permission mappings untuk menu: {$menu->nama_menu}");
    }

    public function groupedByRole($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }
        $items = RoleMenuPermission::with(['menuPermission.menu:id,kode,nama_menu', 'menuPermission.permission:id,nama_permission'])
            ->where('role_id', $roleId)
            ->get();
        $grouped = [];
        foreach ($items as $it) {
            $mp = $it->menuPermission;
            if (!$mp || !$mp->menu) continue;
            $menu = $mp->menu;
            $key = $menu->id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'menu_id' => $menu->id,
                    'menu_code' => $menu->kode,
                    'menu_name' => $menu->nama_menu,
                    'permissions' => [],
                ];
            }
            if ($mp->permission) {
                $grouped[$key]['permissions'][] = [
                    'permission_id' => $mp->permission->id,
                    'nama_permission' => $mp->permission->nama_permission,
                ];
            }
        }
        return $this->successResponse([
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'role_code' => $role->role_code ?? strtoupper(Str::slug($role->name, '_')),
            ],
            'menus' => array_values($grouped),
        ]);
    }

    /**
     * Replace all mappings for a specific role (delete all then add new set)
     */
    public function replaceByRole(Request $request, $roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return $this->errorResponse('Role tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'mappings' => 'required|array',
            'mappings.*.menu_menu_permission_id' => 'nullable|integer|exists:ref_menu_menu_permission,id',
            'mappings.*.menu_id' => 'nullable|integer|exists:ref_menu,id',
            'mappings.*.permission_id' => 'nullable|integer|exists:ref_permission,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            RoleMenuPermission::where('role_id', $roleId)->delete();

            $created = [];
            foreach ($request->mappings as $mapping) {
                $mpId = $mapping['menu_menu_permission_id'] ?? null;
                if (!$mpId) {
                    if (!isset($mapping['menu_id']) || !isset($mapping['permission_id'])) {
                        DB::rollBack();
                        return $this->errorResponse('mapping harus berisi menu_menu_permission_id atau pasangan menu_id + permission_id', 422);
                    }
                    $mp = \App\Models\MenuPermission::firstOrCreate([
                        'menu_id' => $mapping['menu_id'],
                        'permission_id' => $mapping['permission_id'],
                    ]);
                    $mpId = $mp->id;
                }

                $data = RoleMenuPermission::create([
                    'role_id' => $roleId,
                    'menu_menu_permission_id' => $mpId,
                ]);
                $data->load(['role', 'menuPermission.menu', 'menuPermission.permission']);
                $created[] = $data;
            }

            DB::commit();
            return $this->successResponse(['created' => $created], 'Mapping role berhasil diganti');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal mengganti mapping: ' . $e->getMessage(), 500);
        }
    }

}
