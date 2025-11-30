<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Admin',
                'description' => 'Administrator with full access'
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales user with limited access'
            ],
            [
                'name' => 'Operator',
                'description' => 'Operator user with limited access'
            ],
            [
                'name' => 'Operator Warehouse',
                'description' => 'Operator Warehouse with limited access'
            ]
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role['name'],
            ], [
                'description' => $role['description'] ?? null,
            ]);
        }
    }
}
