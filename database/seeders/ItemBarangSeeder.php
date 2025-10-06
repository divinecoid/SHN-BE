<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;

class ItemBarangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all master data
        $jenisBarangs = JenisBarang::all();
        $bentukBarangs = BentukBarang::all();
        $gradeBarangs = GradeBarang::all();

        if ($jenisBarangs->isEmpty() || $bentukBarangs->isEmpty() || $gradeBarangs->isEmpty()) {
            $this->command->warn('Master data (JenisBarang, BentukBarang, atau GradeBarang) masih kosong. Jalankan MasterDataSeeder terlebih dahulu.');
            return;
        }

        $itemCounter = 1;

        // Generate all combinations
        foreach ($jenisBarangs as $jenis) {
            foreach ($bentukBarangs as $bentuk) {
                foreach ($gradeBarangs as $grade) {
                    // Generate kode barang
                    $kodeBarang = $jenis->kode . '-' . $bentuk->kode . '-' . $grade->kode . '-' . str_pad((string)$itemCounter, 3, '0', STR_PAD_LEFT);
                    
                    // Generate nama item
                    $namaItem = $jenis->nama_jenis . ' ' . $bentuk->nama_bentuk . ' ' . $grade->nama;
                    
                    // Generate random dimensions and quantities
                    $panjang = random_int(100, 3000); // 100mm - 3000mm
                    $lebar = random_int(50, 2000);   // 50mm - 2000mm
                    $tebal = random_int(5, 100);     // 5mm - 100mm
                    
                    // Calculate sisa_luas (panjang * lebar in mm²)
                    $sisaLuas = $panjang * $lebar;
                    
                    // Generate quantity
                    $quantity = random_int(1, 100);
                    $quantityTebalSama = random_int(1, $quantity);
                    
                    // Random jenis potongan
                    $jenisPotonganOptions = ['utuh', 'potongan'];
                    $jenisPotongan = $jenisPotonganOptions[array_rand($jenisPotonganOptions)];

                    ItemBarang::create([
                        'kode_barang' => $kodeBarang,
                        'jenis_barang_id' => $jenis->id,
                        'bentuk_barang_id' => $bentuk->id,
                        'grade_barang_id' => $grade->id,
                        'nama_item_barang' => $namaItem,
                        'sisa_luas' => $sisaLuas,
                        'panjang' => $panjang,
                        'lebar' => $lebar,
                        'tebal' => $tebal,
                        'quantity' => $quantity,
                        'quantity_tebal_sama' => $quantityTebalSama,
                        'jenis_potongan' => $jenisPotongan,
                    ]);

                    $itemCounter++;
                }
            }
        }

        $totalItems = $jenisBarangs->count() * $bentukBarangs->count() * $gradeBarangs->count();
        $this->command->info("Berhasil membuat {$totalItems} item barang dari kombinasi:");
        $this->command->info("- {$jenisBarangs->count()} Jenis Barang");
        $this->command->info("- {$bentukBarangs->count()} Bentuk Barang");
        $this->command->info("- {$gradeBarangs->count()} Grade Barang");
    }
}
