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
            ['kode' => 'M006', 'nama_menu' => 'Work Order Planning'],
            ['kode' => 'M007', 'nama_menu' => 'Work Order Actual'],  
            ['kode' => 'M008', 'nama_menu' => 'Purchase Order'],
            ['kode' => 'M009', 'nama_menu' => 'Laporan'],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }
    }
}
