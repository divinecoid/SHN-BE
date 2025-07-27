<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use App\Models\MasterData\JenisMutasiStock;
use App\Models\MasterData\Supplier;
use App\Models\MasterData\Pelanggan;
use App\Models\MasterData\Gudang;
use App\Models\MasterData\Pelaksana;
use App\Models\MasterData\JenisBiaya;
use App\Models\MasterData\ItemBarang;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Jenis Barang
        $jenisBarang1 = JenisBarang::create(['kode' => 'J001', 'nama_jenis' => 'Elektronik']);
        $jenisBarang2 = JenisBarang::create(['kode' => 'J002', 'nama_jenis' => 'Furnitur']);

        // Bentuk Barang
        $bentukBarang1 = BentukBarang::create(['kode' => 'B001', 'nama_bentuk' => 'Kotak']);
        $bentukBarang2 = BentukBarang::create(['kode' => 'B002', 'nama_bentuk' => 'Silinder']);

        // Grade Barang
        $gradeBarang1 = GradeBarang::create(['kode' => 'G001', 'nama' => 'A']);
        $gradeBarang2 = GradeBarang::create(['kode' => 'G002', 'nama' => 'B']);

        // Jenis Mutasi Stock
        JenisMutasiStock::create(['kode' => 'M001', 'mutasi_stock' => 'Masuk', 'jenis' => 'Pembelian']);
        JenisMutasiStock::create(['kode' => 'M002', 'mutasi_stock' => 'Keluar', 'jenis' => 'Penjualan']);

        // Supplier
        $supplier1 = Supplier::create([
            'kode' => 'S001',
            'nama_supplier' => 'PT Sumber Makmur',
            'kota' => 'Jakarta',
            'telepon_hp' => '08123456789',
            'contact_person' => 'Budi'
        ]);
        $supplier2 = Supplier::create([
            'kode' => 'S002',
            'nama_supplier' => 'CV Maju Jaya',
            'kota' => 'Bandung',
            'telepon_hp' => '08234567890',
            'contact_person' => 'Andi'
        ]);

        // Pelanggan
        $pelanggan1 = Pelanggan::create([
            'kode' => 'P001',
            'nama_pelanggan' => 'Toko Sejahtera',
            'kota' => 'Surabaya',
            'telepon_hp' => '0811111111',
            'contact_person' => 'Siti'
        ]);
        $pelanggan2 = Pelanggan::create([
            'kode' => 'P002',
            'nama_pelanggan' => 'UD Sentosa',
            'kota' => 'Semarang',
            'telepon_hp' => '0822222222',
            'contact_person' => 'Joko'
        ]);

        // Gudang
        $gudang1 = Gudang::create([
            'kode' => 'GUD001',
            'nama_gudang' => 'Gudang Utama',
            'telepon_hp' => '0813333333'
        ]);
        $gudang2 = Gudang::create([
            'kode' => 'GUD002',
            'nama_gudang' => 'Gudang Cabang',
            'telepon_hp' => '0814444444'
        ]);

        // Pelaksana
        $pelaksana1 = Pelaksana::create([
            'kode' => 'PLK001',
            'nama_pelaksana' => 'Agus',
            'level' => 'Supervisor'
        ]);
        $pelaksana2 = Pelaksana::create([
            'kode' => 'PLK002',
            'nama_pelaksana' => 'Rina',
            'level' => 'Operator'
        ]);

        // Jenis Biaya
        JenisBiaya::create(['kode' => 'BIA001', 'jenis_biaya' => 'Transportasi']);
        JenisBiaya::create(['kode' => 'BIA002', 'jenis_biaya' => 'Listrik']);

        // Item Barang (relasi ke master data di atas)
        ItemBarang::create([
            'kode_barang' => 'ITM001',
            'jenis_barang_id' => $jenisBarang1->id,
            'bentuk_barang_id' => $bentukBarang1->id,
            'grade_barang_id' => $gradeBarang1->id,
            'nama_item_barang' => 'TV LED 32 inch'
        ]);
        ItemBarang::create([
            'kode_barang' => 'ITM002',
            'jenis_barang_id' => $jenisBarang2->id,
            'bentuk_barang_id' => $bentukBarang2->id,
            'grade_barang_id' => $gradeBarang2->id,
            'nama_item_barang' => 'Meja Bundar'
        ]);
    }
} 