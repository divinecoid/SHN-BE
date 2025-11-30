<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Role;

class RoleCodeSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all();
        foreach ($roles as $role) {
            $code = strtoupper(Str::slug($role->name, '_'));
            $role->update(['role_code' => $code]);
        }
    }
}

