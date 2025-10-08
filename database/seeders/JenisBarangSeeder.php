<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\JenisBarang;

class JenisBarangSeeder extends Seeder
{
    public function run(): void
    {
        $jenisBarangs = [
            ['kode' => 'ALU', 'nama_jenis' => 'ALUMINIUM'],
            ['kode' => 'BRZ', 'nama_jenis' => 'BRONZE'],
            ['kode' => 'KNG', 'nama_jenis' => 'KUNINGAN'],
            ['kode' => 'SUS', 'nama_jenis' => 'STAINLESS'],
            ['kode' => 'TBG', 'nama_jenis' => 'TEMBAGA'],
        ];

        foreach ($jenisBarangs as $jenis) {
            JenisBarang::firstOrCreate(
                ['kode' => $jenis['kode']],
                $jenis
            );
        }

        $this->command->info('Jenis Barang berhasil dibuat: ALU, BRZ, KNG, SUS, TBG');
    }
}
