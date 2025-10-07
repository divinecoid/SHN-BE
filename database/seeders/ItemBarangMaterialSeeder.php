<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use App\Models\MasterData\ItemBarang;

class ItemBarangMaterialSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing master data
        $jenisBarang1 = JenisBarang::where('kode', 'ALU')->first();
        $jenisBarang2 = JenisBarang::where('kode', 'BRZ')->first();
        $jenisBarang3 = JenisBarang::where('kode', 'KNG')->first();
        $jenisBarang4 = JenisBarang::where('kode', 'SUS')->first();
        $jenisBarang5 = JenisBarang::where('kode', 'TBG')->first();

        $bentukBarang1 = BentukBarang::where('kode', 'AS')->first();
        $bentukBarang2 = BentukBarang::where('kode', 'CNU')->first();

        $gradeBarang1 = GradeBarang::where('kode', 'G001')->first();
        $gradeBarang2 = GradeBarang::where('kode', 'G002')->first();
        $gradeBarang3 = GradeBarang::where('kode', 'G003')->first();
        $gradeBarang4 = GradeBarang::where('kode', 'G004')->first();
        $gradeBarang5 = GradeBarang::where('kode', 'G005')->first();

        // Check if all master data exists
        if (!$jenisBarang1 || !$jenisBarang2 || !$jenisBarang3 || !$jenisBarang4 || !$jenisBarang5) {
            $this->command->warn('Jenis Barang (ALU, BRZ, KNG, SUS, TBG) tidak ditemukan. Pastikan data jenis barang sudah ada.');
            return;
        }
        
        if (!$bentukBarang1 || !$bentukBarang2) {
            $this->command->warn('Bentuk Barang (AS, CNU) tidak ditemukan. Pastikan data bentuk barang sudah ada.');
            return;
        }
        
        if (!$gradeBarang1 || !$gradeBarang2 || !$gradeBarang3 || !$gradeBarang4 || !$gradeBarang5) {
            $this->command->warn('Grade Barang (G001-G005) tidak ditemukan. Pastikan data grade barang sudah ada.');
            return;
        }

        // Create item barang with material names
        $items = [
            [
                'kode_barang' => 'ITM001',
                'jenis_barang_id' => $jenisBarang1->id,
                'bentuk_barang_id' => $bentukBarang1->id,
                'grade_barang_id' => $gradeBarang1->id,
                'nama_item_barang' => 'Aluminium Shaft Grade A',
                'sisa_luas' => 3640.60,
                'panjang' => 80.5,
                'lebar' => 45.2,
                'tebal' => 5.0,
                'quantity' => 10.0,
                'quantity_tebal_sama' => 5.0,
                'jenis_potongan' => 'utuh'
            ],
            [
                'kode_barang' => 'ITM002',
                'jenis_barang_id' => $jenisBarang2->id,
                'bentuk_barang_id' => $bentukBarang2->id,
                'grade_barang_id' => $gradeBarang2->id,
                'nama_item_barang' => 'Bronze Canal U Grade B',
                'sisa_luas' => 14400.00,
                'panjang' => 120.0,
                'lebar' => 120.0,
                'tebal' => 3.0,
                'quantity' => 5.0,
                'quantity_tebal_sama' => 3.0,
                'jenis_potongan' => 'utuh'
            ],
            [
                'kode_barang' => 'ITM003',
                'jenis_barang_id' => $jenisBarang3->id,
                'bentuk_barang_id' => $bentukBarang1->id,
                'grade_barang_id' => $gradeBarang3->id,
                'nama_item_barang' => 'Kuningan Shaft Grade C',
                'sisa_luas' => 3500.00,
                'panjang' => 70.0,
                'lebar' => 50.0,
                'tebal' => 0.5,
                'quantity' => 100.0,
                'quantity_tebal_sama' => 50.0,
                'jenis_potongan' => 'potongan'
            ],
            [
                'kode_barang' => 'ITM004',
                'jenis_barang_id' => $jenisBarang4->id,
                'bentuk_barang_id' => $bentukBarang2->id,
                'grade_barang_id' => $gradeBarang4->id,
                'nama_item_barang' => 'Stainless Canal U Premium',
                'sisa_luas' => 150.00,
                'panjang' => 15.0,
                'lebar' => 10.0,
                'tebal' => 2.0,
                'quantity' => 500.0,
                'quantity_tebal_sama' => 200.0,
                'jenis_potongan' => 'utuh'
            ],
            [
                'kode_barang' => 'ITM005',
                'jenis_barang_id' => $jenisBarang5->id,
                'bentuk_barang_id' => $bentukBarang1->id,
                'grade_barang_id' => $gradeBarang5->id,
                'nama_item_barang' => 'Tembaga Shaft Standard',
                'sisa_luas' => 360.00,
                'panjang' => 30.0,
                'lebar' => 12.0,
                'tebal' => 8.0,
                'quantity' => 25.0,
                'quantity_tebal_sama' => 10.0,
                'jenis_potongan' => 'utuh'
            ]
        ];

        $created = 0;
        foreach ($items as $item) {
            // Check if item already exists
            $existing = ItemBarang::where('kode_barang', $item['kode_barang'])->first();
            if (!$existing) {
                ItemBarang::create($item);
                $created++;
            }
        }

        $this->command->info("Berhasil membuat {$created} item barang material baru.");
    }
}
