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
use App\Models\MasterData\JenisTransaksiKas;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // Jenis Barang
        $jenisBarang1 = JenisBarang::create(['kode' => 'J001', 'nama_jenis' => 'Elektronik']);
        $jenisBarang2 = JenisBarang::create(['kode' => 'J002', 'nama_jenis' => 'Furnitur']);
        $jenisBarang3 = JenisBarang::create(['kode' => 'J003', 'nama_jenis' => 'Pakaian']);
        $jenisBarang4 = JenisBarang::create(['kode' => 'J004', 'nama_jenis' => 'Makanan']);
        $jenisBarang5 = JenisBarang::create(['kode' => 'J005', 'nama_jenis' => 'Olahraga']);

        // Bentuk Barang
        $bentukBarang1 = BentukBarang::create(['kode' => 'B001', 'nama_bentuk' => 'Kotak', 'dimensi' => '2D']);
        $bentukBarang2 = BentukBarang::create(['kode' => 'B002', 'nama_bentuk' => 'Silinder', 'dimensi' => '2D']);
        $bentukBarang3 = BentukBarang::create(['kode' => 'B003', 'nama_bentuk' => 'Persegi Panjang', 'dimensi' => '2D']);
        $bentukBarang4 = BentukBarang::create(['kode' => 'B004', 'nama_bentuk' => 'Bundar', 'dimensi' => '1D']);
        $bentukBarang5 = BentukBarang::create(['kode' => 'B005', 'nama_bentuk' => 'Segitiga', 'dimensi' => '1D']);

        // Grade Barang
        $gradeBarang1 = GradeBarang::create(['kode' => 'G001', 'nama' => 'A']);
        $gradeBarang2 = GradeBarang::create(['kode' => 'G002', 'nama' => 'B']);
        $gradeBarang3 = GradeBarang::create(['kode' => 'G003', 'nama' => 'C']);
        $gradeBarang4 = GradeBarang::create(['kode' => 'G004', 'nama' => 'Premium']);
        $gradeBarang5 = GradeBarang::create(['kode' => 'G005', 'nama' => 'Standard']);

        // Jenis Mutasi Stock
        JenisMutasiStock::create(['kode' => 'M001', 'mutasi_stock' => 'Masuk', 'jenis' => 'Pembelian']);
        JenisMutasiStock::create(['kode' => 'M002', 'mutasi_stock' => 'Keluar', 'jenis' => 'Penjualan']);
        JenisMutasiStock::create(['kode' => 'M003', 'mutasi_stock' => 'Masuk', 'jenis' => 'Retur']);
        JenisMutasiStock::create(['kode' => 'M004', 'mutasi_stock' => 'Keluar', 'jenis' => 'Rusak']);
        JenisMutasiStock::create(['kode' => 'M005', 'mutasi_stock' => 'Masuk', 'jenis' => 'Transfer']);

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
        $supplier3 = Supplier::create([
            'kode' => 'S003',
            'nama_supplier' => 'UD Berkah',
            'kota' => 'Surabaya',
            'telepon_hp' => '08345678901',
            'contact_person' => 'Siti'
        ]);
        $supplier4 = Supplier::create([
            'kode' => 'S004',
            'nama_supplier' => 'PT Sejahtera',
            'kota' => 'Semarang',
            'telepon_hp' => '08456789012',
            'contact_person' => 'Joko'
        ]);
        $supplier5 = Supplier::create([
            'kode' => 'S005',
            'nama_supplier' => 'CV Makmur',
            'kota' => 'Yogyakarta',
            'telepon_hp' => '08567890123',
            'contact_person' => 'Rina'
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
        $pelanggan3 = Pelanggan::create([
            'kode' => 'P003',
            'nama_pelanggan' => 'Toko Makmur',
            'kota' => 'Bandung',
            'telepon_hp' => '0833333333',
            'contact_person' => 'Budi'
        ]);
        $pelanggan4 = Pelanggan::create([
            'kode' => 'P004',
            'nama_pelanggan' => 'CV Berkah',
            'kota' => 'Yogyakarta',
            'telepon_hp' => '0844444444',
            'contact_person' => 'Andi'
        ]);
        $pelanggan5 = Pelanggan::create([
            'kode' => 'P005',
            'nama_pelanggan' => 'PT Maju',
            'kota' => 'Jakarta',
            'telepon_hp' => '0855555555',
            'contact_person' => 'Rina'
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
        $gudang3 = Gudang::create([
            'kode' => 'GUD003',
            'nama_gudang' => 'Gudang Pusat',
            'telepon_hp' => '0815555555'
        ]);
        $gudang4 = Gudang::create([
            'kode' => 'GUD004',
            'nama_gudang' => 'Gudang Regional',
            'telepon_hp' => '0816666666'
        ]);
        $gudang5 = Gudang::create([
            'kode' => 'GUD005',
            'nama_gudang' => 'Gudang Distribusi',
            'telepon_hp' => '0817777777'
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
        $pelaksana3 = Pelaksana::create([
            'kode' => 'PLK003',
            'nama_pelaksana' => 'Budi',
            'level' => 'Manager'
        ]);
        $pelaksana4 = Pelaksana::create([
            'kode' => 'PLK004',
            'nama_pelaksana' => 'Siti',
            'level' => 'Staff'
        ]);
        $pelaksana5 = Pelaksana::create([
            'kode' => 'PLK005',
            'nama_pelaksana' => 'Joko',
            'level' => 'Koordinator'
        ]);

        // Jenis Biaya
        $jenisBiaya1 = JenisBiaya::create(['kode' => 'BIA001', 'jenis_biaya' => 'Transportasi']);
        $jenisBiaya2 = JenisBiaya::create(['kode' => 'BIA002', 'jenis_biaya' => 'Listrik']);
        $jenisBiaya3 = JenisBiaya::create(['kode' => 'BIA003', 'jenis_biaya' => 'Air']);
        $jenisBiaya4 = JenisBiaya::create(['kode' => 'BIA004', 'jenis_biaya' => 'Internet']);
        $jenisBiaya5 = JenisBiaya::create(['kode' => 'BIA005', 'jenis_biaya' => 'Maintenance']);

        // Item Barang (relasi ke master data di atas)
        ItemBarang::create([
            'kode_barang' => 'ITM001',
            'jenis_barang_id' => $jenisBarang1->id,
            'bentuk_barang_id' => $bentukBarang1->id,
            'grade_barang_id' => $gradeBarang1->id,
            'nama_item_barang' => 'TV LED 32 inch',
            'sisa_luas' => 3640.60,
            'panjang' => 80.5,
            'lebar' => 45.2,
            'tebal' => 5.0,
            'quantity' => 10.0,
            'quantity_tebal_sama' => 5.0,
            'jenis_potongan' => 'Utuh'
        ]);
        ItemBarang::create([
            'kode_barang' => 'ITM002',
            'jenis_barang_id' => $jenisBarang2->id,
            'bentuk_barang_id' => $bentukBarang2->id,
            'grade_barang_id' => $gradeBarang2->id,
            'nama_item_barang' => 'Meja Bundar',
            'sisa_luas' => 14400.00,
            'panjang' => 120.0,
            'lebar' => 120.0,
            'tebal' => 3.0,
            'quantity' => 5.0,
            'quantity_tebal_sama' => 3.0,
            'jenis_potongan' => 'Utuh'
        ]);
        ItemBarang::create([
            'kode_barang' => 'ITM003',
            'jenis_barang_id' => $jenisBarang3->id,
            'bentuk_barang_id' => $bentukBarang3->id,
            'grade_barang_id' => $gradeBarang3->id,
            'nama_item_barang' => 'Kemeja Pria',
            'sisa_luas' => 3500.00,
            'panjang' => 70.0,
            'lebar' => 50.0,
            'tebal' => 0.5,
            'quantity' => 100.0,
            'quantity_tebal_sama' => 50.0,
            'jenis_potongan' => 'Potongan'
        ]);
        ItemBarang::create([
            'kode_barang' => 'ITM004',
            'jenis_barang_id' => $jenisBarang4->id,
            'bentuk_barang_id' => $bentukBarang4->id,
            'grade_barang_id' => $gradeBarang4->id,
            'nama_item_barang' => 'Snack Pack',
            'sisa_luas' => 150.00,
            'panjang' => 15.0,
            'lebar' => 10.0,
            'tebal' => 2.0,
            'quantity' => 500.0,
            'quantity_tebal_sama' => 200.0,
            'jenis_potongan' => 'Bundle'
        ]);
        ItemBarang::create([
            'kode_barang' => 'ITM005',
            'jenis_barang_id' => $jenisBarang5->id,
            'bentuk_barang_id' => $bentukBarang5->id,
            'grade_barang_id' => $gradeBarang5->id,
            'nama_item_barang' => 'Sepatu Olahraga',
            'sisa_luas' => 360.00,
            'panjang' => 30.0,
            'lebar' => 12.0,
            'tebal' => 8.0,
            'quantity' => 25.0,
            'quantity_tebal_sama' => 10.0,
            'jenis_potongan' => 'Utuh'
        ]);

        // Jenis Transaksi Kas
        JenisTransaksiKas::create([
            'jenis_biaya_id' => $jenisBiaya1->id,
            'keterangan' => 'Transaksi pemasukan kas dari hasil penjualan barang',
            'jumlah' => 1000000.00,
        ]);
        JenisTransaksiKas::create([
            'jenis_biaya_id' => $jenisBiaya1->id,
            'keterangan' => 'Transaksi pengeluaran kas untuk pembelian barang',
            'jumlah' => 500000.00,
        ]);
        JenisTransaksiKas::create([
            'jenis_biaya_id' => $jenisBiaya2->id,
            'keterangan' => 'Transaksi pemasukan kas dari pelunasan piutang',
            'jumlah' => 750000.00,
        ]);
        JenisTransaksiKas::create([
            'jenis_biaya_id' => $jenisBiaya2->id,
            'keterangan' => 'Transaksi pengeluaran kas untuk biaya operasional',
            'jumlah' => 250000.00,
        ]);
        JenisTransaksiKas::create([
            'jenis_biaya_id' => $jenisBiaya3->id,
            'keterangan' => 'Transaksi pemasukan kas dari investasi',
            'jumlah' => 2000000.00,
        ]);
    }
} 