<?php

namespace App\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use App\Models\Transactions\WorkOrderPlanningPelaksana;
use App\Models\Transactions\SaranPlatShaftDasar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class WorkOrderPlanningController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = WorkOrderPlanning::query()
            ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
            ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
            ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id')
            ->leftJoin('trx_work_order_planning_item', 'trx_work_order_planning.id', '=', 'trx_work_order_planning_item.work_order_planning_id')
            ->addSelect([
                'trx_work_order_planning.*',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
                'trx_sales_order.nomor_so',
                DB::raw('COUNT(trx_work_order_planning_item.id) as count')
            ])
            ->groupBy('trx_work_order_planning.id');
        $query = $this->applyFilter($query, $request, ['sales_order.nomor_so', 'nomor_wo', 'tanggal_wo', 'prioritas', 'status']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'nomor_wo' => 'required|string|unique:trx_work_order_planning,nomor_wo',
            'id_sales_order' => 'required|exists:trx_sales_order,id',
            'id_pelanggan' => 'required|exists:ref_pelanggan,id',
            'id_gudang' => 'required|exists:ref_gudang,id',
            'status' => 'required|string',
            'tanggal_wo' => 'required|date',
            'prioritas' => 'required|string',
            'items' => 'required|array',
            'items.*.pelaksana' => 'nullable|array',
            'items.*.pelaksana.*.pelaksana_id' => 'required|exists:ref_pelaksana,id',
            'items.*.pelaksana.*.qty' => 'nullable|integer|min:0',
            'items.*.pelaksana.*.weight' => 'nullable|numeric|min:0',
            'items.*.pelaksana.*.tanggal' => 'nullable|date',
            'items.*.pelaksana.*.jam_mulai' => 'nullable|date_format:H:i',
            'items.*.pelaksana.*.jam_selesai' => 'nullable|date_format:H:i',
            'items.*.pelaksana.*.catatan' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        DB::beginTransaction();
        try {
            // Generate unique ID untuk work order
            $woUniqueId = 'WO-' . date('Ymd') . '-' . strtoupper(uniqid());
            
            // Membuat header Work Order Planning
            $workOrderPlanning = WorkOrderPlanning::create(array_merge($request->only([
                            'nomor_wo',
            'tanggal_wo',
            'id_sales_order',
            'id_pelanggan',
            'id_gudang',
            'prioritas',
            'status',
            ]), [
                'wo_unique_id' => $woUniqueId,
            ]));


            // Jika ada items, simpan items beserta relasi ke ref_jenis_barang, ref_bentuk_barang, ref_grade_barang
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    // Generate unique ID untuk work order item
                    $woItemUniqueId = 'WOI-' . date('Ymd') . '-' . strtoupper(uniqid());
                    
                    $workOrderPlanningItem = WorkOrderPlanningItem::create([
                        'wo_item_unique_id' => $woItemUniqueId,
                        'work_order_planning_id' => $workOrderPlanning->id,
                        'qty' => $item['qty'] ?? 0,
                        'panjang' => $item['panjang'] ?? 0,
                        'lebar' => $item['lebar'] ?? 0,
                        'tebal' => $item['tebal'] ?? 0,
                        'jenis_barang_id' => $item['jenis_barang_id'] ?? null,
                        'berat' => $item['berat'] ?? 0,
                        'satuan' => $item['satuan'] ?? 'PCS',
                        'diskon' => $item['diskon'] ?? 0,
                        'bentuk_barang_id' => $item['bentuk_barang_id'] ?? null,
                        'grade_barang_id' => $item['grade_barang_id'] ?? null,
                        'catatan' => $item['catatan'] ?? null,
                    ]);

                    // Insert pelaksana ke item jika ada
                    if (isset($item['pelaksana']) && is_array($item['pelaksana'])) {
                        foreach ($item['pelaksana'] as $pelaksanaData) {
                            WorkOrderPlanningPelaksana::create([
                                'wo_plan_item_id' => $workOrderPlanningItem->id,
                                'pelaksana_id' => $pelaksanaData['pelaksana_id'],
                                'qty' => $pelaksanaData['qty'] ?? null,
                                'weight' => $pelaksanaData['weight'] ?? null,
                                'tanggal' => $pelaksanaData['tanggal'] ?? null,
                                'jam_mulai' => $pelaksanaData['jam_mulai'] ?? null,
                                'jam_selesai' => $pelaksanaData['jam_selesai'] ?? null,
                                'catatan' => $pelaksanaData['catatan'] ?? null,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            // Load relasi setelah simpan
            $workOrderPlanning->load(['workOrderPlanningItems.hasManyPelaksana.pelaksana', 'salesOrder']);

            return $this->successResponse($workOrderPlanning, 'Work Order Planning berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Work Order Planning: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems.hasManyPelaksana.pelaksana',
            'workOrderPlanningItems.jenisBarang',
            'workOrderPlanningItems.bentukBarang',
            'workOrderPlanningItems.gradeBarang'
        ])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        // Ambil nomor_so dari sales order tanpa memuat object salesOrder
        $nomorSo = \DB::table('trx_sales_order')
            ->where('id', $data->id_sales_order)
            ->value('nomor_so');
        
        // Tambahkan nomor_so ke object utama
        $data->nomor_so = $nomorSo;
        
        return $this->successResponse($data);
    }

    public function showItem($id)
    {
        $data = WorkOrderPlanningItem::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'platDasar', 'hasManySaranPlatShaftDasar.itemBarang'])->find($id);
        return $this->successResponse($data);
    }

    public function updateItem(Request $request, $id)
    {
        // Validasi input
        $validated = $request->validate([
            'qty' => 'nullable|numeric|min:0',
            'panjang' => 'required|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tebal' => 'required|numeric|min:0',
            'jenis_barang_id' => 'nullable|exists:jenis_barangs,id',
            'bentuk_barang_id' => 'nullable|exists:bentuk_barangs,id',
            'grade_barang_id' => 'nullable|exists:grade_barangs,id',
            'catatan' => 'nullable|string|max:500',
            'pelaksana' => 'nullable|array',
            'pelaksana.*.pelaksana_id' => 'nullable|exists:ref_pelaksana,id',
            'pelaksana.*.qty' => 'nullable|integer|min:0',
            'pelaksana.*.weight' => 'nullable|numeric|min:0',
            'pelaksana.*.tanggal' => 'nullable|date',
            'pelaksana.*.jam_mulai' => 'nullable|date_format:H:i',
            'pelaksana.*.jam_selesai' => 'nullable|date_format:H:i',
            'pelaksana.*.catatan' => 'nullable|string|max:500',
            'saran_plat_dasar' => 'nullable|array',
            'saran_plat_dasar.*.item_barang_id' => 'nullable|exists:ref_item_barang,id',
            'saran_plat_dasar.*.is_selected' => 'nullable|boolean',
        ]);

        $data = WorkOrderPlanningItem::find($id);
        if (!$data) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            // Update data item
            $data->update($validated);

            // Update pelaksana jika ada
            if ($request->has('pelaksana') && is_array($request->pelaksana)) {
                // Hapus pelaksana yang lama
                $data->hasManyPelaksana()->delete();

                // Tambah pelaksana yang baru
                foreach ($request->pelaksana as $pelaksanaData) {
                    if (!empty($pelaksanaData['pelaksana_id'])) {
                        WorkOrderPlanningPelaksana::create([
                            'wo_plan_item_id' => $data->id,
                            'pelaksana_id' => $pelaksanaData['pelaksana_id'],
                            'qty' => $pelaksanaData['qty'] ?? null,
                            'weight' => $pelaksanaData['weight'] ?? null,
                            'tanggal' => $pelaksanaData['tanggal'] ?? null,
                            'jam_mulai' => $pelaksanaData['jam_mulai'] ?? null,
                            'jam_selesai' => $pelaksanaData['jam_selesai'] ?? null,
                            'catatan' => $pelaksanaData['catatan'] ?? null,
                        ]);
                    }
                }
            }

            // Update saran plat dasar jika ada
            if ($request->has('saran_plat_dasar') && is_array($request->saran_plat_dasar)) {
                // Hapus saran plat dasar yang lama
                $data->hasManySaranPlatShaftDasar()->delete();

                // Tambah saran plat dasar yang baru
                foreach ($request->saran_plat_dasar as $saranData) {
                    if (!empty($saranData['item_barang_id'])) {
                        SaranPlatShaftDasar::create([
                            'wo_planning_item_id' => $data->id,
                            'item_barang_id' => $saranData['item_barang_id'],
                            'is_selected' => $saranData['is_selected'] ?? false,
                        ]);
                    }
                }

                // Update plat_dasar_id jika ada yang dipilih
                $selectedSaran = collect($request->saran_plat_dasar)->firstWhere('is_selected', true);
                if ($selectedSaran) {
                    $data->update(['plat_dasar_id' => $selectedSaran['item_barang_id']]);
                }
            }

            DB::commit();

            // Load relasi untuk response
            $data->load(['hasManyPelaksana.pelaksana', 'hasManySaranPlatShaftDasar.itemBarang', 'jenisBarang', 'bentukBarang', 'gradeBarang']);

            return $this->successResponse($data, 'Data item berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal mengupdate data item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Tambah pelaksana ke work order planning item
     */
    public function addPelaksana(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'pelaksana_id' => 'required|exists:ref_pelaksana,id',
            'qty' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        try {
            $pelaksana = WorkOrderPlanningPelaksana::create([
                'wo_plan_item_id' => $item->id,
                'pelaksana_id' => $request->pelaksana_id,
                'qty' => $request->qty,
                'weight' => $request->weight,
                'tanggal' => $request->tanggal,
                'jam_mulai' => $request->jam_mulai,
                'jam_selesai' => $request->jam_selesai,
                'catatan' => $request->catatan,
            ]);

            $pelaksana->load('pelaksana');
            return $this->successResponse($pelaksana, 'Pelaksana berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan pelaksana: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Hapus pelaksana dari work order planning item
     */
    public function removePelaksana($itemId, $pelaksanaId)
    {
        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        $pelaksana = WorkOrderPlanningPelaksana::where('id', $pelaksanaId)
            ->where('wo_plan_item_id', $item->id)
            ->first();

        if (!$pelaksana) {
            return $this->errorResponse('Data pelaksana tidak ditemukan', 404);
        }

        try {
            $pelaksana->delete();
            return $this->successResponse(null, 'Pelaksana berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus pelaksana: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update pelaksana individual
     */
    public function updatePelaksana(Request $request, $itemId, $pelaksanaId)
    {
        $validator = Validator::make($request->all(), [
            'qty' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        $pelaksana = WorkOrderPlanningPelaksana::where('id', $pelaksanaId)
            ->where('wo_plan_item_id', $item->id)
            ->first();

        if (!$pelaksana) {
            return $this->errorResponse('Data pelaksana tidak ditemukan', 404);
        }

        try {
            $pelaksana->update($request->only(['qty', 'weight', 'tanggal', 'jam_mulai', 'jam_selesai', 'catatan']));
            $pelaksana->load('pelaksana');
            return $this->successResponse($pelaksana, 'Data pelaksana berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengupdate pelaksana: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems', 
            'salesOrder'
        ])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->update($request->all());
        return $this->successResponse($data);
    }

    public function destroy($id)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems', 
            'salesOrder'
        ])->find($id);

        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        // Hapus data (soft delete)
        $data->delete();

        return $this->successResponse(null, 'Data Work Order Planning berhasil dihapus');
    }

    public function restore($id)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems', 
            'salesOrder'
        ])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->restore();
        return $this->successResponse($data);
    }

    public function forceDelete($id)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems', 
            'salesOrder'
        ])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse($data);
    }

    /* list saran plat dari item barang yang ada di masterdata
    yang memiliki jenis barang, bentuk barang, dan grade barang, dan tebal yang sama 
    dengan jenis barang, bentuk barang, dan grade barang, dan tebal yang diinputkan, 
    dan urutkan dari sisa_luas terbesar ke terkecil */
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
            ->where('sisa_luas', '>', $request->sisa_luas)
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
     * Tambah saran plat/shaft dasar ke work order planning item
     */
    public function addSaranPlatDasar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wo_planning_item_id' => 'required|exists:trx_work_order_planning_item,id',
            'item_barang_id' => 'required|exists:ref_item_barang,id',
            'is_selected' => 'boolean',
            'canvas_data' => 'nullable|json', // JSON data langsung
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $item = WorkOrderPlanningItem::find($request->wo_planning_item_id);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            // Jika is_selected = true, set semua saran lain menjadi false
            if ($request->is_selected) {
                SaranPlatShaftDasar::where('wo_planning_item_id', $item->id)
                    ->update(['is_selected' => false]);
            }

            // Tambah saran plat dasar baru dulu (tanpa canvas_file)
            $saranPlatDasar = SaranPlatShaftDasar::create([
                'wo_planning_item_id' => $item->id,
                'item_barang_id' => $request->item_barang_id,
                'is_selected' => $request->is_selected ?? false,
                'canvas_file' => null,
            ]);

            // Handle canvas data jika ada (setelah saran dibuat)
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
                
                // Update item barang dengan canvas file path (hanya di item barang, tidak di saran)
                $itemBarang = ItemBarang::find($request->item_barang_id);
                if ($itemBarang) {
                    $itemBarang->update(['canvas_file' => $canvasFilePath]);
                }
            }

            // Load relasi untuk response
            $saranPlatDasar->load('itemBarang');

            DB::commit();
            return $this->successResponse($saranPlatDasar, 'Saran plat dasar berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menambahkan saran plat dasar: ' . $e->getMessage(), 500);
        }
    }



    /**
     * Set saran plat dasar sebagai yang dipilih (is_selected = true)
     */
    public function setSelectedPlatDasar($saranId)
    {
        $saranPlatDasar = SaranPlatShaftDasar::find($saranId);
        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran plat dasar tidak ditemukan', 404);
        }

        $item = WorkOrderPlanningItem::find($saranPlatDasar->wo_planning_item_id);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            // Set semua saran lain menjadi false
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

   

    public function printSpkWorkOrder($id)
    {
        $data = WorkOrderPlanning::with(['workOrderPlanningItems.jenisBarang', 'workOrderPlanningItems.bentukBarang', 'workOrderPlanningItems.gradeBarang', 'workOrderPlanningItems.platDasar', 'workOrderPlanningItems.hasManyPelaksana.pelaksana', 'workOrderPlanningItems.hasManySaranPlatShaftDasar.itemBarang', 'salesOrder'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        // map the return to these columns: Jenis Barang, Bentuk Barang, Grade Barang, Ukuran, Qty, Berat, Luas, Plat/Shaft Dasar, Pelaksana (seperated in comma and the weight e.g.: JOSHUA (5kg), ANDI (3kg))
        $mappedData = $data->workOrderPlanningItems->map(function ($item) {
            $pelaksanaList = $item->hasManyPelaksana->map(function ($pelaksana) {
                return $pelaksana->pelaksana->nama_pelaksana . ' (' . ($pelaksana->weight ?? 0) . 'kg)';
            })->implode(', ');
            
            // Get selected plat dasar
            $selectedPlatDasar = $item->hasManySaranPlatShaftDasar->where('is_selected', true)->first();
            $platDasarInfo = '';
            if ($selectedPlatDasar) {
                $platDasarInfo = $selectedPlatDasar->itemBarang->nama_item_barang ?? '';
            } else {
                $platDasarInfo = $item->platDasar->nama_item_barang ?? '';
            }
            
            return [
                'jenis_barang' => $item->jenisBarang->nama_jenis ?? '',
                'bentuk_barang' => $item->bentukBarang->nama_bentuk ?? '',
                'grade_barang' => $item->gradeBarang->nama ?? '',
                'ukuran' => 
                    (is_null($item->panjang) ? '' : ($item->panjang . ' x ')) .
                    (is_null($item->lebar) ? '' : ($item->lebar . ' x ')) .
                    (is_null($item->tebal) ? '' : $item->tebal),
                'qty' => $item->qty,
                'berat' => $item->berat,
                'luas' => $item->panjang * $item->lebar * $item->tebal,
                'plat_dasar' => $platDasarInfo,
                'pelaksana' => $pelaksanaList,
            ];
        });
        
        return $this->successResponse($mappedData);
    }



}
