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
        $query = $this->applyFilter($query, $request, ['sales_order.nomor_so', 'nomor_wo', 'tanggal_wo', 'nama_pelanggan', 'prioritas', 'status']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'nomor_wo' => 'required|string|unique:trx_work_order_planning,nomor_wo',
            'tanggal_wo' => 'required|date',
            'sales_order_id' => 'required|exists:trx_sales_order,id',
            'pelanggan_id' => 'required|exists:ref_pelanggan,id',
            'prioritas' => 'required|string',
            'status' => 'required|string',
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
                'sales_order_id',
                'pelanggan_id',
                'prioritas',
                'status'
            ]));

            // Jika ada items, simpan items beserta relasi ke ref_jenis_barang, ref_bentuk_barang, ref_grade_barang
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $itemBarang = ItemBarang::find($item['plat_dasar_id']);
                    $workOrderPlanningItem = WorkOrderPlanningItem::create([
                        'work_order_planning_id' => $workOrderPlanning->id,
                        'nama_item' => $itemBarang->nama_item_barang ?? null,
                        'qty' => $item['qty'] ?? 0,
                        'panjang' => $item['panjang'] ?? 0,
                        'lebar' => $item['lebar'] ?? 0,
                        'tebal' => $item['tebal'] ?? 0,
                        'jenis_barang_id' => $item['jenis_barang_id'] ?? null,
                        'bentuk_barang_id' => $item['bentuk_barang_id'] ?? null,
                        'grade_barang_id' => $item['grade_barang_id'] ?? null,
                        'satuan' => $item['satuan'] ?? null,
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
        $data = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->update($request->all());
        return $this->successResponse($data);
    }

    public function destroy($id)
    {
        $data = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse($data);
    }

    public function restore($id)
    {
        $data = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->restore();
        return $this->successResponse($data);
    }

    public function forceDelete($id)
    {
        $data = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder'])->find($id);
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

    public function printSpkWorkOrder($id)
    {
        $data = WorkOrderPlanning::with(['workOrderPlanningItems', 'salesOrder'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        // map the return to these columns: Jenis Barang, Bentuk Barang, Grade Barang, Ukuran, Qty, Berat, Luas, Plat/Shaft Dasar, Pelaksana (seperated in comma and the weight e.g.: JOSHUA (5kg), ANDI (3kg))
        $data = $data->map(function ($item) {
            return [
                'jenis_barang' => $item->jenisBarang->nama_jenis,
                'bentuk_barang' => $item->bentukBarang->nama_bentuk,
                'grade_barang' => $item->gradeBarang->nama,
                'Ukuran' => 
                    (is_null($item->panjang) ? '' : ($item->panjang . ' x ')) .
                    (is_null($item->lebar) ? '' : ($item->lebar . ' x ')) .
                    (is_null($item->tebal) ? '' : $item->tebal),
                'qty' => $item->qty,
                'berat' => $item->berat,
                'luas' => $item->panjang * $item->lebar * $item->tebal,
                'plat_dasar' => $item->platDasar->nama_item_barang,
                'pelaksana' => $item->pelaksana->nama_pelaksana,
            ];
        });
        return $this->successResponse($data);
    }



}
