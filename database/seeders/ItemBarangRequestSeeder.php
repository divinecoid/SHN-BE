<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Transactions\ItemBarangRequest;
use App\Models\User;
use App\Models\MasterData\JenisBarang;
use App\Models\MasterData\BentukBarang;
use App\Models\MasterData\GradeBarang;
use Carbon\Carbon;

class ItemBarangRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first user for testing
        $user = User::first();
        
        // Get first jenis, bentuk, and grade barang for testing
        $jenisBarang = JenisBarang::first();
        $bentukBarang = BentukBarang::first();
        $gradeBarang = GradeBarang::first();
        
        if (!$user || !$jenisBarang || !$bentukBarang || !$gradeBarang) {
            $this->command->warn('Required data not found. Please ensure users and master data exist.');
            return;
        }

        // Create sample item barang requests
        $requests = [
            [
                'nomor_request' => 'REQ-' . date('Ymd') . '-001',
                'nama_item_barang' => 'Plat Besi Tebal 10mm',
                'jenis_barang_id' => $jenisBarang->id,
                'bentuk_barang_id' => $bentukBarang->id,
                'grade_barang_id' => $gradeBarang->id,
                'panjang' => 2000.00,
                'lebar' => 1000.00,
                'tebal' => 10.00,
                'quantity' => 5,
                'keterangan' => 'Untuk proyek konstruksi gedung A',
                'status' => 'pending',
                'requested_by' => $user->id,
            ],
            [
                'nomor_request' => 'REQ-' . date('Ymd') . '-002',
                'nama_item_barang' => 'Plat Aluminium 5mm',
                'jenis_barang_id' => $jenisBarang->id,
                'bentuk_barang_id' => $bentukBarang->id,
                'grade_barang_id' => $gradeBarang->id,
                'panjang' => 1500.00,
                'lebar' => 800.00,
                'tebal' => 5.00,
                'quantity' => 10,
                'keterangan' => 'Untuk pembuatan panel listrik',
                'status' => 'approved',
                'requested_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now()->subDays(1),
                'approval_notes' => 'Disetujui untuk pengadaan segera',
            ],
            [
                'nomor_request' => 'REQ-' . date('Ymd') . '-003',
                'nama_item_barang' => 'Besi Hollow 40x40',
                'jenis_barang_id' => $jenisBarang->id,
                'bentuk_barang_id' => $bentukBarang->id,
                'grade_barang_id' => $gradeBarang->id,
                'panjang' => 6000.00,
                'lebar' => 40.00,
                'tebal' => 2.00,
                'quantity' => 20,
                'keterangan' => 'Untuk rangka atap workshop',
                'status' => 'rejected',
                'requested_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now()->subDays(2),
                'approval_notes' => 'Stok masih tersedia, tidak perlu pengadaan baru',
            ],
            [
                'nomor_request' => 'REQ-' . date('Ymd') . '-004',
                'nama_item_barang' => 'Plat Stainless Steel 3mm',
                'jenis_barang_id' => $jenisBarang->id,
                'bentuk_barang_id' => $bentukBarang->id,
                'grade_barang_id' => $gradeBarang->id,
                'panjang' => 1200.00,
                'lebar' => 600.00,
                'tebal' => 3.00,
                'quantity' => 8,
                'keterangan' => 'Untuk pembuatan kitchen set',
                'status' => 'pending',
                'requested_by' => $user->id,
            ],
        ];

        foreach ($requests as $request) {
            ItemBarangRequest::create($request);
        }

        $this->command->info('ItemBarangRequest seeder completed successfully!');
    }
}
