<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Menu;
use App\Models\Permission;
use App\Models\MenuPermission;
use App\Models\Role;
use App\Models\RoleMenuPermission;

class MenuMenuPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        DB::table('ref_role_menu_permission')->truncate();
        DB::table('ref_menu_menu_permission')->truncate();
        DB::table('ref_menu')->truncate();
        Schema::enableForeignKeyConstraints();

        $matrix = [
            'Sales Order' => ['Create', 'Read'],
            'Work Order Actual' => ['Create', 'Read'],
            'Work Order Planning' => ['Create', 'Read'],
            'Master Data' => ['Create', 'Read', 'Update', 'Delete'],
            'User Management' => ['Create', 'Update', 'Delete'],
            'Laporan' => ['View', 'Read'],
            'Purchase Order' => ['Create', 'Read'],
        ];

        $admin = Role::where('name', 'Admin')->first();

        foreach ($matrix as $menuName => $permNames) {
            $kode = strtoupper(Str::slug($menuName, '_'));
            $menu = Menu::create(['kode' => $kode, 'nama_menu' => $menuName]);
            foreach ($permNames as $pname) {
                $perm = Permission::firstOrCreate(['nama_permission' => $pname]);
                $mp = MenuPermission::firstOrCreate([
                    'menu_id' => $menu->id,
                    'permission_id' => $perm->id,
                ]);
                if ($admin) {
                    RoleMenuPermission::firstOrCreate([
                        'role_id' => $admin->id,
                        'menu_menu_permission_id' => $mp->id,
                    ]);
                }
            }
        }

        // Pastikan Admin memiliki semua menu-permission yang ada di sistem
        if ($admin) {
            MenuPermission::query()->select('id')->chunk(200, function ($chunk) use ($admin) {
                foreach ($chunk as $mp) {
                    RoleMenuPermission::firstOrCreate([
                        'role_id' => $admin->id,
                        'menu_menu_permission_id' => $mp->id,
                    ]);
                }
            });
        }
    }
}
