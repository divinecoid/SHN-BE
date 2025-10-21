<?php

namespace App\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use App\Models\Transactions\WorkOrderPlanningItem;
use App\Models\Transactions\SaranPlatShaftDasar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Helpers\FileHelper;

class SaranPlatController extends Controller
{
    use ApiFilterTrait;
    /**
     * Mendapatkan daftar saran plat dasar berdasarkan data yang dikirim melalui body (POST request).
     * Data yang dibutuhkan: jenis_barang_id, bentuk_barang_id, grade_barang_id, tebal, sisa_luas
     */
    public function getSaranPlatDasar(Request $request)
    {
        // Validasi input dari body request
        $validator = Validator::make($request->all(), [
            'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'tebal' => 'required|numeric',
            'sisa_luas' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Ambil user_id dari JWT token
        $currentUserId = auth()->id();
        
        // Ambil data item barang sesuai kriteria yang dikirim melalui body
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])
            ->where('jenis_barang_id', $request->jenis_barang_id)
            ->where('bentuk_barang_id', $request->bentuk_barang_id)
            ->where('grade_barang_id', $request->grade_barang_id)
            ->where('tebal', $request->tebal)
            ->where('sisa_luas', '>=', $request->sisa_luas)
            ->where(function($query) use ($currentUserId) {
                $query->where('is_edit', false)
                      ->orWhereNull('is_edit')
                      ->orWhere('user_id', $currentUserId); // Kalau yang edit user yang sama, tetap return
            })
            ->orderBy('sisa_luas', 'asc')
            ->get();

        // Mapping data untuk response
        $data = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama_item_barang,
                'ukuran' => 
                    (is_null($item->panjang) ? '' : ($item->panjang . ' x ')) .
                    (is_null($item->lebar) ? '' : ($item->lebar . ' x ')) .
                    (is_null($item->tebal) ? '' : $item->tebal),
                'sisa_luas' => $item->sisa_luas,
            ];
        });
        return $this->successResponse($data);
    }

    /**
     * Mendapatkan daftar saran plat dasar untuk jenis potongan 'utuh' berdasarkan data yang dikirim melalui body (POST request).
     * Data yang dibutuhkan: jenis_barang_id, bentuk_barang_id, grade_barang_id, tebal, panjang, lebar
     */
    public function getSaranPlatUtuh(Request $request)
    {
        // Validasi input dari body request
        $validator = Validator::make($request->all(), [
            'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'tebal' => 'required|numeric',
            'panjang' => 'required|numeric',
            'lebar' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Ambil user_id dari JWT token
        $currentUserId = auth()->id();
        
        // Ambil data item barang sesuai kriteria yang dikirim melalui body
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])
            ->where('jenis_barang_id', $request->jenis_barang_id)
            ->where('bentuk_barang_id', $request->bentuk_barang_id)
            ->where('grade_barang_id', $request->grade_barang_id)
            ->where('tebal', $request->tebal)
            ->where('panjang', '=', $request->panjang) // Panjang harus sama, tidak boleh lebih besar
            ->where('lebar', '=', $request->lebar) // Lebar harus sama, tidak boleh lebih besar
            ->where('jenis_potongan', 'utuh') // Hanya ambil yang jenis_potongan = 'utuh'
            ->where(function($query) use ($currentUserId) {
                $query->where('is_edit', false)
                      ->orWhereNull('is_edit')
                      ->orWhere('user_id', $currentUserId); // Kalau yang edit user yang sama, tetap return
            })
            ->orderBy('sisa_luas', 'asc')
            ->get();

        // Mapping data untuk response
        $data = $data->map(function ($item) {
            return [
                'id' => $item->id,
                'nama' => $item->nama_item_barang,
                'ukuran' => 
                    (is_null($item->panjang) ? '' : ($item->panjang . ' x ')) .
                    (is_null($item->lebar) ? '' : ($item->lebar . ' x ')) .
                    (is_null($item->tebal) ? '' : $item->tebal),
                'sisa_luas' => $item->sisa_luas,
            ];
        });
        return $this->successResponse($data);
    }

    /**
     * Simpan canvas data/image ke ItemBarang (tanpa keterikatan WO item)
     */
    public function addSaranPlatDasar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_barang_id' => 'required|exists:ref_item_barang,id',
            'canvas_data' => 'nullable|json', // JSON data langsung
            'canvas_image' => 'nullable|string', // Base64 JPG data
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Tidak membuat record saran; hanya menyimpan canvas ke ItemBarang terkait
            $saved = [
                'canvas_file' => null,
                'canvas_image' => null,
            ];

            // Handle canvas data jika ada (setelah semua saran dibuat)
            if ($request->has('canvas_data') && !empty($request->canvas_data)) {
                // Convert JSON to file
                $canvasData = $request->canvas_data;
                $fileName = 'canvas.json';
                $folderPath = 'canvas/' . $request->item_barang_id;
                $fullPath = storage_path('app/public/' . $folderPath);
                
                // Buat folder jika belum ada
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0755, true);
                }
                
                // Tulis JSON ke file
                $filePath = $fullPath . '/' . $fileName;
                file_put_contents($filePath, $canvasData);
                
                $canvasFilePath = $folderPath . '/' . $fileName;
                
                // Update item barang dengan canvas file path dan sisa_luas (hanya di item barang, tidak di saran)
                $itemBarang = ItemBarang::find($request->item_barang_id);
                if ($itemBarang) {
                    $updateData = ['canvas_file' => $canvasFilePath];
                    
                    // Update sisa_luas dari totalArea di metadata canvas
                    $canvasDataDecoded = json_decode($canvasData, true);
                    if (isset($canvasDataDecoded['metadata']['totalArea'])) {
                        $updateData['sisa_luas'] = $canvasDataDecoded['metadata']['totalArea'];
                    }
                    
                    $itemBarang->update($updateData);
                }
                $saved['canvas_file'] = $canvasFilePath;
            }

            // Handle canvas image jika ada (base64 JPG)
            if ($request->has('canvas_image') && !empty($request->canvas_image)) {
                $folderPath = 'canvas/' . $request->item_barang_id;
                $fileName = 'canvas_image';
                
                // Save base64 as JPG using FileHelper
                $result = FileHelper::saveBase64AsJpg($request->canvas_image, $folderPath, $fileName);
                
                if ($result['success']) {
                    // Update item barang dengan canvas image path
                    $itemBarang = ItemBarang::find($request->item_barang_id);
                    if ($itemBarang) {
                        $itemBarang->update(['canvas_image' => $result['data']['path']]);
                    }
                    $saved['canvas_image'] = $result['data']['path'];
                } else {
                    // Log error tapi jangan gagalkan seluruh proses
                    Log::error('Failed to save canvas image: ' . $result['message']);
                }
            }
            DB::commit();
            return $this->successResponse($saved, 'Canvas berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan canvas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Set saran plat dasar sebagai yang dipilih (is_selected = true)
     */
    public function setSelectedPlatDasar(Request $request, $saranId)
    {
        $validator = Validator::make($request->all(), [
            'wo_item_unique_id' => 'required|string|exists:trx_work_order_planning_item,wo_item_unique_id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Cari item berdasarkan wo_item_unique_id
        $item = WorkOrderPlanningItem::where('wo_item_unique_id', $request->wo_item_unique_id)->first();
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        // Cari saran plat dasar
        $saranPlatDasar = SaranPlatShaftDasar::find($saranId);
        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran plat dasar tidak ditemukan', 404);
        }

        // Validasi bahwa saran tersebut milik item yang sama
        if ($saranPlatDasar->wo_planning_item_id != $item->id) {
            return $this->errorResponse('Saran plat dasar tidak sesuai dengan item yang dipilih', 400);
        }

        DB::beginTransaction();
        try {
            // Set semua saran lain menjadi false untuk item ini
            SaranPlatShaftDasar::where('wo_planning_item_id', $item->id)
                ->update(['is_selected' => false]);

            // Set saran yang dipilih menjadi true
            $saranPlatDasar->update(['is_selected' => true]);

            // Update plat_dasar_id di work order planning item
            $item->update(['plat_dasar_id' => $saranPlatDasar->item_barang_id]);

            // Load relasi untuk response
            $saranPlatDasar->load('itemBarang');

            DB::commit();
            return $this->successResponse($saranPlatDasar, 'Saran plat dasar berhasil diset sebagai yang dipilih');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal set saran plat dasar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get semua saran plat/shaft dasar untuk item tertentu
     */
    public function getSaranPlatDasarByItem($itemId)
    {
        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        // Ambil user_id dari JWT token
        $currentUserId = auth()->id();
        
        $saranPlatDasar = SaranPlatShaftDasar::with('itemBarang')
            ->where('wo_planning_item_id', $item->id)
            ->whereHas('itemBarang', function($query) use ($currentUserId) {
                $query->where(function($q) use ($currentUserId) {
                    $q->where('is_edit', false)
                      ->orWhereNull('is_edit')
                      ->orWhere('user_id', $currentUserId); // Kalau yang edit user yang sama, tetap return
                });
            })
            ->orderBy('is_selected', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse($saranPlatDasar);
    }

    /**
     * Download canvas file untuk saran plat dasar
     */
    public function downloadCanvasFile($saranId)
    {
        $saranPlatDasar = SaranPlatShaftDasar::find($saranId);
        
        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran tidak ditemukan', 404);
        }

        if (!$saranPlatDasar->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan', 404);
        }

        $filePath = storage_path('app/public/' . $saranPlatDasar->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        return response()->download($filePath);
    }

    /**
     * Get canvas file content untuk saran plat dasar
     */
    public function getCanvasFile($saranId)
    {
        $saranPlatDasar = SaranPlatShaftDasar::find($saranId);
        
        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran tidak ditemukan', 404);
        }

        if (!$saranPlatDasar->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan', 404);
        }

        $filePath = storage_path('app/public/' . $saranPlatDasar->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        $content = file_get_contents($filePath);
        
        return response()->json([
            'success' => true,
            'data' => [
                'canvas_data' => json_decode($content, true),
                'file_path' => $saranPlatDasar->canvas_file
            ]
        ]);
    }

    /**
     * Get canvas file content berdasarkan item barang ID
     */
    public function getCanvasFileByItemBarang($itemBarangId)
    {
        $itemBarang = ItemBarang::find($itemBarangId);
        
        if (!$itemBarang) {
            return $this->errorResponse('Data item barang tidak ditemukan', 404);
        }

        if (!$itemBarang->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan untuk item ini', 404);
        }

        $filePath = storage_path('app/public/' . $itemBarang->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        $content = file_get_contents($filePath);
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_barang_id' => $itemBarangId,
                'canvas_data' => json_decode($content, true),
                'file_path' => $itemBarang->canvas_file
            ]
        ]);
    }

    /**
     * Get canvas file content berdasarkan item barang ID
     */
    public function getCanvasByItemId($itemBarangId)
    {
        $itemBarang = ItemBarang::find($itemBarangId);
        
        if (!$itemBarang) {
            return $this->errorResponse('Data item barang tidak ditemukan', 404);
        }

        if (!$itemBarang->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan untuk item ini', 404);
        }

        $filePath = storage_path('app/public/' . $itemBarang->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        $content = file_get_contents($filePath);
        
        // Return hanya isi JSON canvas saja
        return response()->json(json_decode($content, true));
    }

    /**
     * Get canvas image berdasarkan item barang ID
     */
    public function getCanvasImageByItemId($itemBarangId)
    {
        $itemBarang = ItemBarang::find($itemBarangId);
        
        if (!$itemBarang) {
            return $this->errorResponse('Data item barang tidak ditemukan', 404);
        }

        if (!$itemBarang->canvas_image) {
            return $this->errorResponse('Canvas image tidak ditemukan untuk item ini', 404);
        }

        $imagePath = storage_path('app/public/' . $itemBarang->canvas_image);
        
        if (!file_exists($imagePath)) {
            return $this->errorResponse('File image tidak ditemukan di storage', 404);
        }

        $imageData = file_get_contents($imagePath);
        $base64 = base64_encode($imageData);
        
        return response()->json([
            'canvas_image' => 'data:image/jpeg;base64,' . $base64
        ]);
    }
}
