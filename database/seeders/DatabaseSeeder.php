<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            JenisBarangSeeder::class,
            BentukBarangSeeder::class,
            GradeBarangSeeder::class,
            MasterDataSeeder::class,
            PermissionSeeder::class,
            MenuSeeder::class,
            GudangSeeder::class,
            ItemBarangSeeder::class,
            ItemBarangMaterialSeeder::class,
        ]);
    }
}
