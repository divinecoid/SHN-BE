<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\BentukBarang;

class BentukBarangSeeder extends Seeder
{
    public function run(): void
    {
        $bentukBarangs = [
            [
                'kode' => 'AS',
                'nama_bentuk' => 'Aluminium Shaft',
                'dimensi' => '50x50x1000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'CNU',
                'nama_bentuk' => 'Canal U',
                'dimensi' => '100x50x2000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'PLT',
                'nama_bentuk' => 'Plate',
                'dimensi' => '1000x500x10',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'PIP',
                'nama_bentuk' => 'Pipe',
                'dimensi' => '50x2000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'ANG',
                'nama_bentuk' => 'Angle',
                'dimensi' => '50x50x2000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($bentukBarangs as $bentuk) {
            BentukBarang::firstOrCreate(
                ['kode' => $bentuk['kode']],
                $bentuk
            );
        }

        $this->command->info('Bentuk Barang berhasil dibuat: AS, CNU, PLT, PIP, ANG');
    }
}
