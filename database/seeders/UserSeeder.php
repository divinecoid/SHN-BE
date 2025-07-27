<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $admin->roles()->attach($adminRole->id);
        }

        // Create sales user
        $sales = User::create([
            'username' => 'sales',
            'name' => 'Sales User',
            'email' => 'sales@example.com',
            'password' => Hash::make('password123'),
        ]);
        $salesRole = Role::where('name', 'Sales')->first();
        if ($salesRole) {
            $sales->roles()->attach($salesRole->id);
        }

        // Create operator user
        $operator = User::create([
            'username' => 'operator',
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'password' => Hash::make('password123'),
        ]);
        $operatorRole = Role::where('name', 'Operator')->first();
        if ($operatorRole) {
            $operator->roles()->attach($operatorRole->id);
        }

        // Create warehouse operator
        $warehouse = User::create([
            'username' => 'warehouse',
            'name' => 'Warehouse Operator',
            'email' => 'warehouse@example.com',
            'password' => Hash::make('password123'),
        ]);
        $warehouseRole = Role::where('name', 'Operator Warehouse')->first();
        if ($warehouseRole) {
            $warehouse->roles()->attach($warehouseRole->id);
        }
    }
} 