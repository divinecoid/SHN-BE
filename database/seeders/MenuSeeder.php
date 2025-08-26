<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            ['kode' => 'M001', 'nama_menu' => 'Dashboard'],
            ['kode' => 'M002', 'nama_menu' => 'Master Data'],
            ['kode' => 'M003', 'nama_menu' => 'Penerimaan Barang'],
            ['kode' => 'M004', 'nama_menu' => 'Sales Order'],
            ['kode' => 'M005', 'nama_menu' => 'Laporan'],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
