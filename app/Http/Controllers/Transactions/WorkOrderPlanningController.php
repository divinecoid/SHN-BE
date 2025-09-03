<?php

namespace App\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use App\Models\Transactions\WorkOrderPlanningPelaksana;
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
        $query = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder']);
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
            'id_pelaksana' => 'required|exists:ref_pelaksana,id',
            'status' => 'required|string',
            'tanggal_wo' => 'required|date',
            'prioritas' => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        DB::beginTransaction();
        try {
            // Membuat header Work Order Planning
            $workOrderPlanning = WorkOrderPlanning::create($request->only([
                            'nomor_wo',
            'tanggal_wo',
            'id_sales_order',
            'id_pelanggan',
            'id_gudang',
            'id_pelaksana',
            'prioritas',
            'status',
            ]));

            // Jika ada items, simpan items beserta relasi ke ref_jenis_barang, ref_bentuk_barang, ref_grade_barang
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $workOrderPlanningItem = WorkOrderPlanningItem::create([
                        'work_order_planning_id' => $workOrderPlanning->id,
                        'qty' => $item['qty'] ?? 0,
                        'panjang' => $item['panjang'] ?? 0,
                        'lebar' => $item['lebar'] ?? 0,
                        'tebal' => $item['tebal'] ?? 0,
                        'plat_dasar_id' => $item['plat_dasar_id'] ?? null,
                        'jenis_barang_id' => $item['jenis_barang_id'] ?? null,
                        'bentuk_barang_id' => $item['bentuk_barang_id'] ?? null,
                        'grade_barang_id' => $item['grade_barang_id'] ?? null,
                        'catatan' => $item['catatan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            // Load relasi setelah simpan
            $workOrderPlanning->load(['workOrderPlanningItems', 'salesOrder']);

            return $this->successResponse($workOrderPlanning, 'Work Order Planning berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Work Order Planning: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems', 
            'salesOrder'
        ])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
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
                            'work_order_planning_item_id' => $data->id,
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
                'work_order_planning_item_id' => $item->id,
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
            ->where('work_order_planning_item_id', $item->id)
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
            ->where('work_order_planning_item_id', $item->id)
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
    public function getSaranPlatDasar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'tebal' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $data = ItemBarang::with(['jenisBarang', 'bentukBarang', 'gradeBarang'])
        ->where('jenis_barang_id', $request->jenis_barang_id)
        ->where('bentuk_barang_id', $request->bentuk_barang_id)
        ->where('grade_barang_id', $request->grade_barang_id)
        ->where('tebal', $request->tebal)
        ->orderBy('sisa_luas', 'desc')
        ->get();


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

    /*
    Set saran plat dasar ke work order planning item, 1 wo planning item bisa memiliki lebih dari 1 saran plat dasar
    */
    public function setSaranPlatDasar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wo_planning_item_id' => 'required|exists:trx_work_order_planning_item,id',
            'plat_dasar_id' => 'required|exists:ref_item_barang,id',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        $workOrderPlanningItem = WorkOrderPlanningItem::find($request->wo_planning_item_id);
        if (!$workOrderPlanningItem) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $workOrderPlanningItem->plat_dasar_id = $request->plat_dasar_id;
        $workOrderPlanningItem->save();
        return $this->successResponse($workOrderPlanningItem);
    }

    /**
     * Tambah saran plat/shaft dasar ke work order planning item
     */
    public function addSaranPlatDasar(Request $request, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'item_barang_id' => 'required|exists:ref_item_barang,id',
            'is_selected' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $item = WorkOrderPlanningItem::find($itemId);
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

            // Tambah saran plat dasar baru
            $saranPlatDasar = SaranPlatShaftDasar::create([
                'wo_planning_item_id' => $item->id,
                'item_barang_id' => $request->item_barang_id,
                'is_selected' => $request->is_selected ?? false,
            ]);

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
     * Update saran plat/shaft dasar
     */
    public function updateSaranPlatDasar(Request $request, $itemId, $saranId)
    {
        $validator = Validator::make($request->all(), [
            'is_selected' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        $saranPlatDasar = SaranPlatShaftDasar::where('id', $saranId)
            ->where('wo_planning_item_id', $item->id)
            ->first();

        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran plat dasar tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            // Jika is_selected = true, set semua saran lain menjadi false
            if ($request->is_selected) {
                SaranPlatShaftDasar::where('wo_planning_item_id', $item->id)
                    ->where('id', '!=', $saranId)
                    ->update(['is_selected' => false]);
            }

            // Update saran plat dasar
            $saranPlatDasar->update($request->only(['is_selected']));

            // Load relasi untuk response
            $saranPlatDasar->load('itemBarang');

            DB::commit();
            return $this->successResponse($saranPlatDasar, 'Saran plat dasar berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal mengupdate saran plat dasar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Hapus saran plat/shaft dasar
     */
    public function removeSaranPlatDasar($itemId, $saranId)
    {
        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        $saranPlatDasar = SaranPlatShaftDasar::where('wo_planning_item_id', $item->id)
            ->where('id', $saranId)
            ->first();

        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran plat dasar tidak ditemukan', 404);
        }

        try {
            $saranPlatDasar->delete();
            return $this->successResponse(null, 'Saran plat dasar berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus saran plat dasar: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Set saran plat dasar sebagai yang dipilih (is_selected = true)
     */
    public function setSelectedPlatDasar($itemId, $saranId)
    {
        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        $saranPlatDasar = SaranPlatShaftDasar::where('id', $saranId)
            ->where('wo_planning_item_id', $item->id)
            ->first();

        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran plat dasar tidak ditemukan', 404);
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

        $saranPlatDasar = SaranPlatShaftDasar::with('itemBarang')
            ->where('wo_planning_item_id', $item->id)
            ->orderBy('is_selected', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse($saranPlatDasar);
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
