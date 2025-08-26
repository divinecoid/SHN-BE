<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['nama_permission' => 'Create'],
            ['nama_permission' => 'Read'],
            ['nama_permission' => 'Update'],
            ['nama_permission' => 'Delete'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
