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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

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
            'panjang' => 'required|numeric',
            'lebar' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Ambil user_id dari JWT token
        $currentUserId = auth()->id();
        
        $areaNeeded = (float)$request->panjang * (float)$request->lebar;
        $reqPanjang = (float)$request->panjang;
        $reqLebar = (float)$request->lebar;

        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])
            ->where('jenis_barang_id', $request->jenis_barang_id)
            ->where('bentuk_barang_id', $request->bentuk_barang_id)
            ->where('grade_barang_id', $request->grade_barang_id)
            ->where('tebal', $request->tebal)
            ->where('sisa_luas', '>=', $areaNeeded)
            ->where(function($query) use ($currentUserId) {
                $query->where('is_edit', false)
                      ->orWhereNull('is_edit')
                      ->orWhere('user_id', $currentUserId); // Kalau yang edit user yang sama, tetap return
            })
            ->orderBy('sisa_luas', 'asc')
            ->get();
        
        $data = $data->filter(function ($item) use ($reqPanjang, $reqLebar) {
            $fallbackCheck = function($itm) use ($reqPanjang, $reqLebar) {
                $p = $itm->panjang;
                $l = $itm->lebar;
                if ($p === null || $l === null) {
                    return false;
                }
                return ((float)$p >= (float)$reqPanjang) && ((float)$l >= (float)$reqLebar);
            };

            if (!$item->canvas_file) {
                return $fallbackCheck($item);
            }
            $path = storage_path('app/public/' . $item->canvas_file);
            if (!file_exists($path)) {
                return $fallbackCheck($item);
            }
            $json = json_decode(file_get_contents($path), true);
            if (!is_array($json)) {
                return $fallbackCheck($item);
            }
            return $this->canFitInCanvas($json, (float)$reqPanjang, (float)$reqLebar);
        });

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

        $perPage = (int) $request->input('per_page', $this->getPerPageDefault());
        $page = (int) $request->input('page', 1);
        $total = $data->count();
        $items = $data->forPage($page, $perPage)->values();
        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );
        return response()->json($this->paginateResponse($paginator, $items));
    }

    private function canFitInCanvas(array $json, float $reqW, float $reqH): bool
    {
        $cw = $json['baseContainer']['width'] ?? null;
        $ch = $json['baseContainer']['height'] ?? null;
        $meta = $json['metadata'] ?? [];
        if (($cw === null || $ch === null) && !empty($meta['containerSize']) && strpos($meta['containerSize'], '×') !== false) {
            $parts = explode('×', $meta['containerSize']);
            $cw = isset($parts[0]) ? (float) $parts[0] : null;
            $ch = isset($parts[1]) ? (float) $parts[1] : null;
        }
        if ($cw === null || $ch === null) {
            return false;
        }
        if ($reqW <= 0 || $reqH <= 0 || $reqW > $cw || $reqH > $ch) {
            return false;
        }
        $boxes = $json['boxes'] ?? [];
        $yEdges = [0.0, (float)$ch];
        foreach ($boxes as $b) {
            $y0 = isset($b['y']) ? (float)$b['y'] : 0.0;
            $h = isset($b['height']) ? (float)$b['height'] : 0.0;
            $y1 = $y0 + $h;
            $yEdges[] = $y0;
            $yEdges[] = $y1;
        }
        $yEdges = array_values(array_unique($yEdges));
        sort($yEdges);
        $n = count($yEdges);
        for ($i = 0; $i < $n - 1; $i++) {
            $stripTop = $yEdges[$i];
            $stripBot = $yEdges[$i + 1];
            $stripH = $stripBot - $stripTop;
            if ($stripH <= 0) {
                continue;
            }
            $occ = [];
            foreach ($boxes as $b) {
                $y0 = isset($b['y']) ? (float)$b['y'] : 0.0;
                $h = isset($b['height']) ? (float)$b['height'] : 0.0;
                $y1 = $y0 + $h;
                if ($y0 < $stripBot && $y1 > $stripTop) {
                    $x0 = isset($b['x']) ? (float)$b['x'] : 0.0;
                    $w = isset($b['width']) ? (float)$b['width'] : 0.0;
                    $x1 = $x0 + $w;
                    $x0 = max(0.0, min($x0, (float)$cw));
                    $x1 = max(0.0, min($x1, (float)$cw));
                    if ($x1 > $x0) {
                        $occ[] = [$x0, $x1];
                    }
                }
            }
            usort($occ, function ($a, $b) { return $a[0] <=> $b[0]; });
            $merged = [];
            foreach ($occ as $iv) {
                if (empty($merged) || $iv[0] > $merged[count($merged) - 1][1]) {
                    $merged[] = [$iv[0], $iv[1]];
                } else {
                    $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $iv[1]);
                }
            }
            $free = [];
            $cur = 0.0;
            foreach ($merged as $iv) {
                if ($iv[0] > $cur) {
                    $free[] = [$cur, $iv[0]];
                }
                $cur = max($cur, $iv[1]);
            }
            if ($cur < (float)$cw) {
                $free[] = [$cur, (float)$cw];
            }
            foreach ($free as $seg) {
                $w0 = $seg[0];
                $w1 = $seg[1];
                if (($w1 - $w0) < $reqW) {
                    continue;
                }
                $xL = $w0;
                $xR = $w1;
                $accH = $stripH;
                $k = $i + 1;
                while ($accH < $reqH && $k < $n - 1) {
                    $nextTop = $yEdges[$k];
                    $nextBot = $yEdges[$k + 1];
                    $nextH = $nextBot - $nextTop;
                    if ($nextH <= 0) { $k++; continue; }
                    $occ2 = [];
                    foreach ($boxes as $b) {
                        $y0 = isset($b['y']) ? (float)$b['y'] : 0.0;
                        $h = isset($b['height']) ? (float)$b['height'] : 0.0;
                        $y1 = $y0 + $h;
                        if ($y0 < $nextBot && $y1 > $nextTop) {
                            $x0 = isset($b['x']) ? (float)$b['x'] : 0.0;
                            $w = isset($b['width']) ? (float)$b['width'] : 0.0;
                            $x1 = $x0 + $w;
                            $x0 = max(0.0, min($x0, (float)$cw));
                            $x1 = max(0.0, min($x1, (float)$cw));
                            if ($x1 > $x0) {
                                $occ2[] = [$x0, $x1];
                            }
                        }
                    }
                    usort($occ2, function ($a, $b) { return $a[0] <=> $b[0]; });
                    $merged2 = [];
                    foreach ($occ2 as $iv) {
                        if (empty($merged2) || $iv[0] > $merged2[count($merged2) - 1][1]) {
                            $merged2[] = [$iv[0], $iv[1]];
                        } else {
                            $merged2[count($merged2) - 1][1] = max($merged2[count($merged2) - 1][1], $iv[1]);
                        }
                    }
                    $free2 = [];
                    $cur2 = 0.0;
                    foreach ($merged2 as $iv) {
                        if ($iv[0] > $cur2) {
                            $free2[] = [$cur2, $iv[0]];
                        }
                        $cur2 = max($cur2, $iv[1]);
                    }
                    if ($cur2 < (float)$cw) {
                        $free2[] = [$cur2, (float)$cw];
                    }
                    $foundOverlap = false;
                    foreach ($free2 as $seg2) {
                        $xxL = max($xL, $seg2[0]);
                        $xxR = min($xR, $seg2[1]);
                        if (($xxR - $xxL) >= $reqW) {
                            $xL = $xxL;
                            $xR = $xxR;
                            $accH += $nextH;
                            $foundOverlap = true;
                            break;
                        }
                    }
                    if (!$foundOverlap) {
                        break;
                    }
                    $k++;
                }
                if ($accH >= $reqH) {
                    return true;
                }
            }
        }
        return false;
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
        return $this->successResponse($data->values()->all());
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
                
                $itemBarang = ItemBarang::find($request->item_barang_id);
                if ($itemBarang) {
                    $updateData = ['canvas_file' => $canvasFilePath];
                    $canvasDataDecoded = json_decode($canvasData, true);
                    if (is_array($canvasDataDecoded)) {
                        $containerWidth = $canvasDataDecoded['baseContainer']['width'] ?? null;
                        $containerHeight = $canvasDataDecoded['baseContainer']['height'] ?? null;
                        $metadata = $canvasDataDecoded['metadata'] ?? [];
                        if (($containerWidth === null || $containerHeight === null) && !empty($metadata['containerSize']) && strpos($metadata['containerSize'], '×') !== false) {
                            $parts = explode('×', $metadata['containerSize']);
                            $containerWidth = isset($parts[0]) ? (float) $parts[0] : null;
                            $containerHeight = isset($parts[1]) ? (float) $parts[1] : null;
                        }
                        $boxes = $canvasDataDecoded['boxes'] ?? [];
                        $totalWidthUsed = 0.0;
                        $totalHeightUsed = 0.0;
                        $totalAreaBoxes = 0.0;
                        foreach ($boxes as $box) {
                            $w = isset($box['width']) ? (float) $box['width'] : 0.0;
                            $h = isset($box['height']) ? (float) $box['height'] : 0.0;
                            $totalWidthUsed += $w;
                            $totalHeightUsed += $h;
                            $totalAreaBoxes += ($w * $h);
                        }
                        $sisaPanjang = ($containerWidth !== null) ? max($containerWidth - $totalWidthUsed, 0) : null;
                        $sisaLebar = ($containerHeight !== null) ? max($containerHeight - $totalHeightUsed, 0) : null;
                        $containerArea = $metadata['containerArea'] ?? (($containerWidth !== null && $containerHeight !== null) ? ($containerWidth * $containerHeight) : null);
                        $totalArea = $metadata['totalArea'] ?? $totalAreaBoxes;
                        $sisaLuas = ($containerArea !== null && $totalArea !== null) ? max($containerArea - $totalArea, 0) : null;
                        
                        
                        if ($sisaLuas !== null) {
                            $updateData['sisa_luas'] = $sisaLuas;
                        }
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
            return $this->successResponse(['canvas_image' => null], 'Canvas image tidak ditemukan untuk item ini');
        }

        $imagePath = storage_path('app/public/' . $itemBarang->canvas_image);
        
        if (!file_exists($imagePath)) {
            return $this->successResponse(['canvas_image' => null], 'File image tidak ditemukan di storage');
        }

        $imageData = file_get_contents($imagePath);
        $base64 = base64_encode($imageData);
        
        return response()->json([
            'canvas_image' => 'data:image/jpeg;base64,' . $base64
        ]);
    }
}
