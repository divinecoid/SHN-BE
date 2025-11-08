<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Transactions\WorkOrderActual;
use App\Models\Transactions\WorkOrderActualItem;
use App\Models\Transactions\WorkOrderActualPelaksana;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Helpers\FileHelper;

class WorkOrderActualController extends Controller
{
    use ApiFilterTrait;

    /**
     * Display a listing of work order actuals
     */
    public function index(Request $request)
    {
        try {
            // List ringkas untuk halaman daftar: kurangi atribut agar hemat bandwidth
            $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));

            $query = WorkOrderActual::query()
                ->select([
                    'trx_work_order_actual.id',
                    'trx_work_order_actual.work_order_planning_id',
                    'trx_work_order_actual.tanggal_actual',
                    'trx_work_order_actual.status',
                    'trx_work_order_planning.nomor_wo',
                    'trx_sales_order.nomor_so',
                    'ref_pelanggan.nama_pelanggan',
                    'ref_gudang.nama_gudang',
                ])
                ->withCount(['workOrderActualItems as jumlah_item'])
                ->join('trx_work_order_planning', 'trx_work_order_actual.work_order_planning_id', '=', 'trx_work_order_planning.id')
                ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
                ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
                ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id');

            // Pencarian & filter sesuai kebutuhan list
            $query = $this->applyFilter($query, $request, [
                'trx_work_order_actual.status',
                'trx_work_order_planning.nomor_wo',
                'trx_sales_order.nomor_so',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
            ]);

            if ($request->filled('status')) {
                $query->where('trx_work_order_actual.status', $request->input('status'));
            }
            if ($request->filled('id_pelanggan')) {
                $query->where('trx_work_order_planning.id_pelanggan', $request->input('id_pelanggan'));
            }
            if ($request->filled('id_gudang')) {
                $query->where('trx_work_order_planning.id_gudang', $request->input('id_gudang'));
            }
            if ($request->filled('nomor_wo')) {
                $query->where('trx_work_order_planning.nomor_wo', 'like', "%" . $request->input('nomor_wo') . "%");
            }
            if ($request->filled('nomor_so')) {
                $query->where('trx_sales_order.nomor_so', 'like', "%" . $request->input('nomor_so') . "%");
            }

            // Filter periode berdasarkan tanggal actual jika ada
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('trx_work_order_actual.tanggal_actual', [
                    $request->input('date_from'),
                    $request->input('date_to')
                ]);
            }

            $data = $query->paginate($perPage);
            $items = collect($data->items());
            return response()->json($this->paginateResponse($data, $items));

        } catch (\Exception $e) {
            Log::error('Error fetching work order actuals (lite): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data work order actual (list)',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Base64 image for Work Order Actual (header foto_bukti) by Actual ID
     */
    public function getActualImageBase64($id)
    {
        try {
            $actual = WorkOrderActual::find($id);
            if (!$actual) {
                return $this->errorResponse('Work Order Actual tidak ditemukan', 404);
            }

            if (empty($actual->foto_bukti)) {
                return $this->errorResponse('Foto bukti tidak tersedia untuk WO Actual ini', 404);
            }

            $imagePath = storage_path('app/public/' . $actual->foto_bukti);
            if (!file_exists($imagePath)) {
                return $this->errorResponse('File image tidak ditemukan di storage', 404);
            }

            $base64 = base64_encode(file_get_contents($imagePath));
            return $this->successResponse([
                'wo_actual_id' => $actual->id,
                'file_path' => $actual->foto_bukti,
                'foto_bukti_base64' => 'data:image/jpeg;base64,' . $base64,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get Base64 image for Work Order Actual Item by Item ID
     */
    public function getActualItemImageBase64($itemId)
    {
        try {
            $item = WorkOrderActualItem::find($itemId);
            if (!$item) {
                return $this->errorResponse('Work Order Actual Item tidak ditemukan', 404);
            }

            if (empty($item->foto_bukti)) {
                return $this->errorResponse('Foto bukti tidak tersedia untuk WO Actual Item ini', 404);
            }

            $imagePath = storage_path('app/public/' . $item->foto_bukti);
            if (!file_exists($imagePath)) {
                return $this->errorResponse('File image tidak ditemukan di storage', 404);
            }

            $base64 = base64_encode(file_get_contents($imagePath));
            return $this->successResponse([
                'wo_actual_item_id' => $item->id,
                'wo_actual_id' => $item->work_order_actual_id,
                'file_path' => $item->foto_bukti,
                'foto_bukti_base64' => 'data:image/jpeg;base64,' . $base64,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil image item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all Base64 images for Work Order Actual Items by Actual ID
     */
    public function getAllActualItemImagesBase64($actualId)
    {
        try {
            $actual = WorkOrderActual::find($actualId);
            if (!$actual) {
                return $this->errorResponse('Work Order Actual tidak ditemukan', 404);
            }

            $items = $actual->workOrderActualItems;
            if ($items->isEmpty()) {
                return $this->successResponse([
                    'wo_actual_id' => $actual->id,
                    'total_images' => 0,
                    'images' => [],
                ], 'Tidak ada item atau foto bukti untuk WO Actual ini');
            }

            $images = [];
            foreach ($items as $item) {
                $base64 = null;
                if (!empty($item->foto_bukti)) {
                    $imagePath = storage_path('app/public/' . $item->foto_bukti);
                    if (file_exists($imagePath)) {
                        $base64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imagePath));
                    }
                }

                $images[] = [
                    'wo_actual_item_id' => $item->id,
                    'file_path' => $item->foto_bukti,
                    'foto_bukti_base64' => $base64,
                ];
            }

            return $this->successResponse([
                'wo_actual_id' => $actual->id,
                'total_images' => count($images),
                'images' => $images,
            ], 'Images berhasil diambil');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stream image file for Work Order Actual (header foto_bukti) by Actual ID
     */
    public function streamActualImage($id)
    {
        try {
            $actual = WorkOrderActual::find($id);
            if (!$actual) {
                return $this->errorResponse('Work Order Actual tidak ditemukan', 404);
            }

            if (empty($actual->foto_bukti)) {
                return $this->errorResponse('Foto bukti tidak tersedia untuk WO Actual ini', 404);
            }

            // Pastikan path relatif bersih (tanpa leading slash)
            $relativePath = ltrim($actual->foto_bukti, '/');

            // Resolve full path dengan fallback yang lebih kuat (termasuk nested folder)
            $fullPath = $this->resolvePublicImagePath($relativePath, [
                // kandidat umum
                'foto_bukti.jpg',
                // beberapa project menyimpan di subfolder 'header/'
                'header/foto_bukti.jpg',
            ]);

            if (!$fullPath || !file_exists($fullPath)) {
                Log::warning('WO Actual header image not found', [
                    'wo_actual_id' => $actual->id,
                    'stored_path' => $actual->foto_bukti,
                    'resolved_path' => $fullPath,
                ]);
                return $this->errorResponse('File image tidak ditemukan di storage', 404);
            }

            // Set MIME eksplisit
            $mime = @mime_content_type($fullPath) ?: 'image/jpeg';
            $size = @filesize($fullPath) ?: null;
            $headers = [
                'Content-Type'                => $mime,
                'Cache-Control'               => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                      => 'no-cache',
                'Expires'                     => '0',
                'Content-Disposition'         => 'inline; filename="' . basename($fullPath) . '"',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods'=> 'GET, OPTIONS',
                'Access-Control-Allow-Headers'=> 'Authorization, Content-Type',
            ];
            if ($size) {
                $headers['Content-Length'] = $size;
            }
            return response()->make(file_get_contents($fullPath), 200, $headers);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menampilkan image: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Stream image file for Work Order Actual Item by Item ID
     */
    public function streamActualItemImage($itemId)
    {
        try {
            $item = WorkOrderActualItem::find($itemId);
            if (!$item) {
                return $this->errorResponse('Work Order Actual Item tidak ditemukan', 404);
            }

            if (empty($item->foto_bukti)) {
                return $this->errorResponse('Foto bukti tidak tersedia untuk WO Actual Item ini', 404);
            }

            // Pastikan path relatif bersih (tanpa leading slash)
            $relativePath = ltrim($item->foto_bukti, '/');

            // Resolve full path dengan fallback yang lebih kuat (termasuk nested folder)
            $fullPath = $this->resolvePublicImagePath($relativePath, [
                'foto_bukti.jpg',
            ]);

            if (!$fullPath || !file_exists($fullPath)) {
                Log::warning('WO Actual item image not found', [
                    'wo_actual_item_id' => $item->id,
                    'wo_actual_id' => $item->work_order_actual_id,
                    'stored_path' => $item->foto_bukti,
                    'resolved_path' => $fullPath,
                ]);
                return $this->errorResponse('File image tidak ditemukan di storage', 404);
            }

            $mime = @mime_content_type($fullPath) ?: 'image/jpeg';
            $size = @filesize($fullPath) ?: null;
            $headers = [
                'Content-Type'                => $mime,
                'Cache-Control'               => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'                      => 'no-cache',
                'Expires'                     => '0',
                'Content-Disposition'         => 'inline; filename="' . basename($fullPath) . '"',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods'=> 'GET, OPTIONS',
                'Access-Control-Allow-Headers'=> 'Authorization, Content-Type',
            ];
            if ($size) {
                $headers['Content-Length'] = $size;
            }
            return response()->make(file_get_contents($fullPath), 200, $headers);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menampilkan image item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resolve image path on public disk with robust fallbacks.
     * - Cleans relative path
     * - Tries direct file
     * - Tries candidate files inside the folder
     * - Recursively searches for first image file under the folder
     */
    private function resolvePublicImagePath(string $relativePath, array $candidates = []): ?string
    {
        $relativePath = ltrim($relativePath, '/');

        // Direct file
        $directPath = Storage::disk('public')->path($relativePath);
        if (file_exists($directPath)) {
            return $directPath;
        }

        // If path points to a folder or file missing, try candidates inside the folder
        $folder = rtrim($relativePath, '/');
        foreach ($candidates as $candidate) {
            $candidateRel = $folder . '/' . ltrim($candidate, '/');
            if (Storage::disk('public')->exists($candidateRel)) {
                return Storage::disk('public')->path($candidateRel);
            }
        }

        // Heuristic: if basename has no dot → treat as folder and search recursively
        $base = basename($relativePath);
        if (strpos($base, '.') === false) {
            // Search recursively for first image file
            $files = Storage::disk('public')->allFiles($folder);
            foreach ($files as $f) {
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $f)) {
                    return Storage::disk('public')->path($f);
                }
            }
        }

        return null;
    }

    /**
     * Report header Work Order Actual (atribut parent saja)
     */
    public function report(Request $request)
    {
        try {
            $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));

            $query = WorkOrderActual::query()
                ->join('trx_work_order_planning', 'trx_work_order_actual.work_order_planning_id', '=', 'trx_work_order_planning.id')
                ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
                ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
                ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id')
                ->withCount(['workOrderActualItems as jumlah_item'])
                ->addSelect([
                    // Actual header
                    'trx_work_order_actual.id',
                    'trx_work_order_actual.work_order_planning_id',
                    'trx_work_order_actual.tanggal_actual',
                    'trx_work_order_actual.status',
                    'trx_work_order_actual.catatan',
                    'trx_work_order_actual.foto_bukti',
                    'trx_work_order_actual.created_at',
                    'trx_work_order_actual.updated_at',
                    // Planning header context
                    'trx_work_order_planning.nomor_wo',
                    'trx_work_order_planning.tanggal_wo',
                    'trx_work_order_planning.id_pelanggan',
                    'trx_work_order_planning.id_gudang',
                    'trx_work_order_planning.id_pelaksana',
                    'trx_work_order_planning.prioritas',
                    'trx_work_order_planning.handover_method',
                    // Names and references
                    'ref_pelanggan.nama_pelanggan',
                    'ref_gudang.nama_gudang',
                    'trx_sales_order.nomor_so',
                ]);

            // Search & sort
            $query = $this->applyFilter($query, $request, [
                'trx_work_order_actual.status',
                'trx_work_order_planning.nomor_wo',
                'trx_sales_order.nomor_so',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
            ]);

            // Specific filters
            if ($request->filled('status')) {
                $query->where('trx_work_order_actual.status', $request->input('status'));
            }
            if ($request->filled('id_pelanggan')) {
                $query->where('trx_work_order_planning.id_pelanggan', $request->input('id_pelanggan'));
            }
            if ($request->filled('id_gudang')) {
                $query->where('trx_work_order_planning.id_gudang', $request->input('id_gudang'));
            }
            if ($request->filled('nomor_wo')) {
                $query->where('trx_work_order_planning.nomor_wo', 'like', "%" . $request->input('nomor_wo') . "%");
            }
            if ($request->filled('nomor_so')) {
                $query->where('trx_sales_order.nomor_so', 'like', "%" . $request->input('nomor_so') . "%");
            }

            // Date range filter for actual
            $start = $request->input('tanggal_actual_start');
            $end = $request->input('tanggal_actual_end');
            if ($start && $end) {
                $query->whereBetween('trx_work_order_actual.tanggal_actual', [$start, $end]);
            } elseif ($start) {
                $query->whereDate('trx_work_order_actual.tanggal_actual', '>=', $start);
            } elseif ($end) {
                $query->whereDate('trx_work_order_actual.tanggal_actual', '<=', $end);
            }

            // Default sort to avoid ambiguous 'id' with joins
            if (!$request->filled('sort') && !$request->filled('sort_by')) {
                $query->orderBy('trx_work_order_actual.tanggal_actual', 'desc');
            }

            $data = $query->paginate($perPage);
            $items = collect($data->items());

            return response()->json($this->paginateResponse($data, $items));
        } catch (\Exception $e) {
            Log::error('Error fetching work order actual report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil report work order actual',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified work order actual
     */
    public function show($id)
    {
        try {
            $workOrderActual = WorkOrderActual::with([
                'workOrderPlanning',
                'workOrderActualItems.workOrderPlanningItem.platDasar.jenisBarang',
                'workOrderActualItems.workOrderPlanningItem.platDasar.bentukBarang',
                'workOrderActualItems.workOrderPlanningItem.platDasar.gradeBarang',
                'workOrderActualItems.hasManyPelaksana.pelaksana'
            ])->find($id);

            if (!$workOrderActual) {
                return response()->json([
                    'success' => false,
                    'message' => 'Work order actual tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data work order actual berhasil diambil',
                'data' => $workOrderActual
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching work order actual: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data work order actual',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function saveWorkOrderActual(Request $request)
    {
        // Validasi request tidak boleh kosong
        if (empty($request->all()) || count($request->all()) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak boleh kosong'
            ], 400);
        }
        try {
            // Validasi input untuk struktur baru (ADD hanya terima base64 untuk foto)
            $validator = Validator::make($request->all(), [
                'foto_bukti' => 'required|string',
                // Allow null for create flow; we'll auto-create if not found
                'actualWorkOrderId' => 'nullable|integer',
                'planningWorkOrderId' => 'required|integer',
                'items' => 'required|array',
                'items.*.qtyActual' => 'required|numeric|min:0',
                // Hanya terima berat (actual) sebagai nama field
                'items.*.berat' => 'required|numeric|min:0',
                // Optional base64 foto bukti per item
                'items.*.foto_bukti' => 'nullable|string',
                'items.*.assignments' => 'required|array',
                'items.*.assignments.*.id' => 'nullable|integer',
                'items.*.assignments.*.pelaksana_id' => 'required|integer',
                'items.*.assignments.*.qty' => 'required|integer|min:1',
                // Terima salah satu: weight atau berat untuk assignments
                'items.*.assignments.*.weight' => 'required_without:items.*.assignments.*.berat|numeric|min:0',
                'items.*.assignments.*.berat' => 'required_without:items.*.assignments.*.weight|numeric|min:0',
                'items.*.assignments.*.pelaksana' => 'nullable|string',
                'items.*.assignments.*.pelaksana_id' => 'required|integer',
                'items.*.assignments.*.tanggal' => 'required|date',
                'items.*.assignments.*.jamMulai' => 'required|string',
                'items.*.assignments.*.jamSelesai' => 'required|string',
                'items.*.assignments.*.catatan' => 'nullable|string',
                'items.*.assignments.*.status' => 'nullable|string',
                'items.*.timestamp' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $results = [];
            $actualWorkOrderId = $request->input('actualWorkOrderId');
            $planningWorkOrderId = $request->input('planningWorkOrderId');

            $planningWorkOrder = WorkOrderPlanning::find($planningWorkOrderId);
            if (!$planningWorkOrder) {
                throw new \Exception("PlanningWorkOrder dengan ID {$planningWorkOrderId} tidak ditemukan");
            }
            $planningWorkOrder->close_wo_at = now();
            $planningWorkOrder->status = 'Selesai';
            $planningWorkOrder->save();

            $actualWorkOrder = WorkOrderActual::find($actualWorkOrderId);
            if (!$actualWorkOrder) {
                // Jika tidak ditemukan, buat WorkOrderActual baru berdasarkan planning
                $actualWorkOrder = WorkOrderActual::create([
                    'work_order_planning_id' => $planningWorkOrderId,
                    'tanggal_actual' => now(),
                    'status' => 'Proses',
                    'catatan' => null,
                ]);
                $actualWorkOrderId = $actualWorkOrder->id;
                Log::channel('stderr')->info("Created new ActualWorkOrder ID: {$actualWorkOrderId}");
            } else {
                Log::channel('stderr')->info("ActualWorkOrder ID: {$actualWorkOrderId}");
            }

            // Simpan foto_bukti jika dikirim (base64), menggunakan logic seperti canvas_image
            $fotoBuktiBase64 = $request->input('foto_bukti');
            if (!empty($fotoBuktiBase64)) {
                $folderPath = 'work-order-actual/' . $actualWorkOrderId;
                $fileName = 'foto_bukti';
                $result = FileHelper::saveBase64AsJpg($fotoBuktiBase64, $folderPath, $fileName);
                if ($result['success'] ?? false) {
                    $actualWorkOrder->foto_bukti = $result['data']['path'] ?? null;
                    $actualWorkOrder->save();
                } else {
                    Log::error('Failed to save foto_bukti: ' . ($result['message'] ?? 'Unknown error'));
                }
            }
            // Tambahan path/object tidak diterima untuk ADD; hanya base64 yang diproses di atas
            $actualWorkOrderItems = $actualWorkOrder->workOrderActualItems;
            foreach ($actualWorkOrderItems as $actualWorkOrderItem) {
                // log actualworkorderitem id
                Log::channel('stderr')->info("ActualWorkOrderItem ID: {$actualWorkOrderItem->id}");
                $pelaksanaData = $actualWorkOrderItem->getPelaksanaWithDetails()->get();
                $pelaksanaData->each(function ($pelaksana) {
                    Log::channel('stderr')->info("Pelaksana ID: {$pelaksana->id}");
                    $pelaksana->delete();
                });
                
                // Pastikan semua pelaksana yang terkait sudah di-soft-delete
                // Gunakan withTrashed() untuk memastikan tidak ada data yang terlewat
                $remainingPelaksana = $actualWorkOrderItem->hasManyPelaksana()->withTrashed()->get();
                $remainingPelaksana->each(function ($pelaksana) {
                    $pelaksana->delete();
                });
                $actualWorkOrderItem->delete();
            }

            $items = $request->input('items', []);

            foreach ($items as $planItemId => $data) {
                // Cek apakah WorkOrderPlanningItem ada
                $planningItem = WorkOrderPlanningItem::find($planItemId);
                if (!$planningItem) {
                    throw new \Exception("WorkOrderPlanningItem dengan ID {$planItemId} tidak ditemukan");
                }

                // Cek apakah sudah ada WorkOrderActualItem untuk planning item ini
                $existingActualItem = WorkOrderActualItem::where('wo_plan_item_id', $planItemId)->first();
                
                if ($existingActualItem) {
                    // Hapus semua data pelaksana yang terkait
                    WorkOrderActualPelaksana::where('wo_actual_item_id', $existingActualItem->id)->delete();
                    
                    // Hapus WorkOrderActualItem yang sudah ada
                    $existingActualItem->delete();
                }

                // Gunakan actualWorkOrderId dari request
                $workOrderActualId = $actualWorkOrderId;

                // Validasi jika work_order_actual_id null
                if (is_null($workOrderActualId)) {
                    throw new \Exception("actualWorkOrderId tidak boleh null");
                }

                // Buat WorkOrderActualItem baru via relasi agar foreign key terasosiasi otomatis
                $beratActualValue = $data['berat'] ?? 0;
                $actualItem = $actualWorkOrder->workOrderActualItems()->create([
                    'wo_plan_item_id' => $planItemId,
                    'qty_actual' => $data['qtyActual'],
                    'berat_actual' => $beratActualValue,
                    'jenis_barang_id' => $planningItem->jenis_barang_id,
                    'bentuk_barang_id' => $planningItem->bentuk_barang_id,
                    'grade_barang_id' => $planningItem->grade_barang_id,
                    'plat_dasar_id' => $planningItem->plat_dasar_id,
                    'panjang_actual' => $planningItem->panjang,
                    'lebar_actual' => $planningItem->lebar,
                    'tebal_actual' => $planningItem->tebal,
                    'catatan' => $planningItem->catatan,
                ]);

                // Simpan foto bukti per item jika ada (base64), folder terpisah per child
                if (!empty($data['foto_bukti'])) {
                    try {
                        $itemFolder = 'work-order-actual/' . $workOrderActualId . '/items/' . $actualItem->id;
                        $result = FileHelper::saveBase64AsJpg($data['foto_bukti'], $itemFolder, 'foto_bukti');
                        if ($result['success'] ?? false) {
                            $actualItem->foto_bukti = $result['data']['path'] ?? null;
                            $actualItem->save();
                        } else {
                            Log::error('Failed to save item foto_bukti: ' . ($result['message'] ?? 'Unknown error'));
                        }
                    } catch (\Exception $e) {
                        Log::error('Exception saving item foto_bukti: ' . $e->getMessage());
                    }
                }
                // ADD hanya menerima base64; tidak ada fallback path/object di item

                // Buat data pelaksana (assignments)
                $pelaksanaData = [];
                foreach ($data['assignments'] as $assignment) {
                    $pelaksanaData[] = [
                        'wo_actual_item_id' => $actualItem->id,
                        'pelaksana_id' => $assignment['pelaksana_id'],
                        'qty' => $assignment['qty'],
                        'weight' => $assignment['weight'] ?? $assignment['berat'],
                        'tanggal' => $assignment['tanggal'],
                        'jam_mulai' => $assignment['jamMulai'],
                        'jam_selesai' => $assignment['jamSelesai'],
                        'catatan' => $assignment['catatan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert semua data pelaksana sekaligus
                WorkOrderActualPelaksana::insert($pelaksanaData);

                $results[] = [
                    'plan_item_id' => $planItemId,
                    'actual_item_id' => $actualItem->id,
                    'qty_actual' => $data['qtyActual'],
                    'berat' => $beratActualValue,
                    'foto_bukti' => $actualItem->foto_bukti,
                    'assignments_count' => count($data['assignments']),
                    'timestamp' => $data['timestamp']
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data WorkOrderActual berhasil disimpan'
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
