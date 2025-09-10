<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\Gudang;
use Illuminate\Support\Str;

class GudangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 parent gudang
        $parents = collect();
        for ($i = 1; $i <= 10; $i++) {
            $parents->push(Gudang::create([
                'kode' => 'GDG-P' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'nama_gudang' => 'Gudang Induk ' . $i,
                'tipe_gudang' => 'induk',
                'parent_id' => null,
                'telepon_hp' => '08' . random_int(100000000, 999999999),
                'kapasitas' => random_int(1000, 10000),
            ]));
        }

        // Create 90 child gudang distributed under parents
        for ($i = 1; $i <= 90; $i++) {
            $parent = $parents->random();
            Gudang::create([
                'kode' => 'GDG-C' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'nama_gudang' => 'Gudang Cabang ' . $i,
                'tipe_gudang' => 'cabang',
                'parent_id' => $parent->id,
                'telepon_hp' => '08' . random_int(100000000, 999999999),
                'kapasitas' => random_int(500, 5000),
            ]);
        }
    }
}
