<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\GradeBarang;

class GradeBarangSeeder extends Seeder
{
    public function run(): void
    {
        $gradeBarangs = [
            [
                'kode' => 'G001',
                'nama' => 'Grade A',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'G002',
                'nama' => 'Grade B',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'G003',
                'nama' => 'Grade C',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'G004',
                'nama' => 'Grade D',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'kode' => 'G005',
                'nama' => 'Grade E',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($gradeBarangs as $grade) {
            GradeBarang::firstOrCreate(
                ['kode' => $grade['kode']],
                $grade
            );
        }

        $this->command->info('Grade Barang berhasil dibuat: G001, G002, G003, G004, G005');
    }
}
