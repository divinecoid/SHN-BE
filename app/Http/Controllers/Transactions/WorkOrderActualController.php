<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Transactions\WorkOrderActual;
use App\Models\Transactions\WorkOrderActualItem;
use App\Models\Transactions\WorkOrderActualPelaksana;
use App\Models\Transactions\WorkOrderPlanning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkOrderActualController extends Controller
{
    use ApiFilterTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = WorkOrderActual::query()
            ->leftJoin('trx_work_order_planning', 'trx_work_order_actual.work_order_planning_id', '=', 'trx_work_order_planning.id')
            ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
            ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
            ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id')
            ->addSelect([
                'trx_work_order_actual.*',
                'trx_work_order_planning.nomor_wo',
                'trx_work_order_planning.tanggal_wo',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
                'trx_sales_order.nomor_so'
            ]);

        $query = $this->applyFilter($query, $request, [
            'trx_work_order_planning.nomor_wo',
            'trx_work_order_actual.tanggal_actual',
            'trx_work_order_actual.status',
            'ref_pelanggan.nama_pelanggan'
        ]);

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'work_order_planning_id' => 'required|exists:trx_work_order_planning,id',
            'tanggal_actual' => 'required|date',
            'status' => 'required|string|in:draft,in_progress,completed,cancelled',
            'catatan' => 'nullable|string|max:1000',
            'items' => 'nullable|array',
            'items.*.work_order_planning_item_id' => 'required|exists:trx_work_order_planning_item,id',
            'items.*.qty_actual' => 'required|numeric|min:0',
            'items.*.berat_actual' => 'nullable|numeric|min:0',
            'items.*.catatan' => 'nullable|string|max:500',
            'pelaksana' => 'nullable|array',
            'pelaksana.*.pelaksana_id' => 'required|exists:ref_pelaksana,id',
            'pelaksana.*.qty' => 'nullable|numeric|min:0',
            'pelaksana.*.weight' => 'nullable|numeric|min:0',
            'pelaksana.*.tanggal' => 'nullable|date',
            'pelaksana.*.jam_mulai' => 'nullable|date_format:H:i',
            'pelaksana.*.jam_selesai' => 'nullable|date_format:H:i',
            'pelaksana.*.catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Create WorkOrderActual
            $workOrderActual = WorkOrderActual::create($request->only([
                'work_order_planning_id',
                'tanggal_actual',
                'status',
                'catatan'
            ]));

            // Create WorkOrderActualItems if provided
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    WorkOrderActualItem::create([
                        'work_order_actual_id' => $workOrderActual->id,
                        'work_order_planning_item_id' => $item['work_order_planning_item_id'],
                        'qty_actual' => $item['qty_actual'],
                        'berat_actual' => $item['berat_actual'] ?? null,
                        'catatan' => $item['catatan'] ?? null,
                    ]);
                }
            }

            // Create WorkOrderActualPelaksana if provided
            if ($request->has('pelaksana') && is_array($request->pelaksana)) {
                foreach ($request->pelaksana as $pelaksanaData) {
                    WorkOrderActualPelaksana::create([
                        'work_order_actual_id' => $workOrderActual->id,
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

            DB::commit();

            // Load relations for response
            $workOrderActual->load([
                'workOrderActualItems.workOrderPlanningItem',
                'workOrderActualPelaksana.pelaksana',
                'workOrderPlanning'
            ]);

            return $this->successResponse($workOrderActual, 'Work Order Actual berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Work Order Actual: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $data = WorkOrderActual::with([
            'workOrderActualItems.workOrderPlanningItem.jenisBarang',
            'workOrderActualItems.workOrderPlanningItem.bentukBarang',
            'workOrderActualItems.workOrderPlanningItem.gradeBarang',
            'workOrderActualPelaksana.pelaksana',
            'workOrderPlanning.salesOrder',
            'workOrderPlanning.pelanggan',
            'workOrderPlanning.gudang'
        ])->find($id);

        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        return $this->successResponse($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tanggal_actual' => 'nullable|date',
            'status' => 'nullable|string|in:draft,in_progress,completed,cancelled',
            'catatan' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $workOrderActual->update($request->only([
                'tanggal_actual',
                'status',
                'catatan'
            ]));

            $workOrderActual->load([
                'workOrderActualItems.workOrderPlanningItem',
                'workOrderActualPelaksana.pelaksana',
                'workOrderPlanning'
            ]);

            return $this->successResponse($workOrderActual, 'Work Order Actual berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengupdate Work Order Actual: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $workOrderActual->delete();
            return $this->successResponse(null, 'Work Order Actual berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus Work Order Actual: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restore the specified resource.
     */
    public function restore($id)
    {
        $workOrderActual = WorkOrderActual::withTrashed()->find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        try {
            $workOrderActual->restore();
            return $this->successResponse($workOrderActual, 'Work Order Actual berhasil direstore');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal merestore Work Order Actual: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add item to WorkOrderActual
     */
    public function addItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'work_order_planning_item_id' => 'required|exists:trx_work_order_planning_item,id',
            'qty_actual' => 'required|numeric|min:0',
            'berat_actual' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data Work Order Actual tidak ditemukan', 404);
        }

        try {
            $item = WorkOrderActualItem::create([
                'work_order_actual_id' => $workOrderActual->id,
                'work_order_planning_item_id' => $request->work_order_planning_item_id,
                'qty_actual' => $request->qty_actual,
                'berat_actual' => $request->berat_actual,
                'catatan' => $request->catatan,
            ]);

            $item->load('workOrderPlanningItem');
            return $this->successResponse($item, 'Item berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update item in WorkOrderActual
     */
    public function updateItem(Request $request, $id, $itemId)
    {
        $validator = Validator::make($request->all(), [
            'qty_actual' => 'nullable|numeric|min:0',
            'berat_actual' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data Work Order Actual tidak ditemukan', 404);
        }

        $item = WorkOrderActualItem::where('id', $itemId)
            ->where('work_order_actual_id', $workOrderActual->id)
            ->first();

        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        try {
            $item->update($request->only(['qty_actual', 'berat_actual', 'catatan']));
            $item->load('workOrderPlanningItem');
            return $this->successResponse($item, 'Item berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengupdate item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove item from WorkOrderActual
     */
    public function removeItem($id, $itemId)
    {
        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data Work Order Actual tidak ditemukan', 404);
        }

        $item = WorkOrderActualItem::where('id', $itemId)
            ->where('work_order_actual_id', $workOrderActual->id)
            ->first();

        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        try {
            $item->delete();
            return $this->successResponse(null, 'Item berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus item: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add pelaksana to WorkOrderActual
     */
    public function addPelaksana(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pelaksana_id' => 'required|exists:ref_pelaksana,id',
            'qty' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data Work Order Actual tidak ditemukan', 404);
        }

        try {
            $pelaksana = WorkOrderActualPelaksana::create([
                'work_order_actual_id' => $workOrderActual->id,
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
     * Update pelaksana in WorkOrderActual
     */
    public function updatePelaksana(Request $request, $id, $pelaksanaId)
    {
        $validator = Validator::make($request->all(), [
            'qty' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'tanggal' => 'nullable|date',
            'jam_mulai' => 'nullable|date_format:H:i',
            'jam_selesai' => 'nullable|date_format:H:i',
            'catatan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data Work Order Actual tidak ditemukan', 404);
        }

        $pelaksana = WorkOrderActualPelaksana::where('id', $pelaksanaId)
            ->where('work_order_actual_id', $workOrderActual->id)
            ->first();

        if (!$pelaksana) {
            return $this->errorResponse('Data pelaksana tidak ditemukan', 404);
        }

        try {
            $pelaksana->update($request->only(['qty', 'weight', 'tanggal', 'jam_mulai', 'jam_selesai', 'catatan']));
            $pelaksana->load('pelaksana');
            return $this->successResponse($pelaksana, 'Pelaksana berhasil diupdate');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengupdate pelaksana: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove pelaksana from WorkOrderActual
     */
    public function removePelaksana($id, $pelaksanaId)
    {
        $workOrderActual = WorkOrderActual::find($id);
        if (!$workOrderActual) {
            return $this->errorResponse('Data Work Order Actual tidak ditemukan', 404);
        }

        $pelaksana = WorkOrderActualPelaksana::where('id', $pelaksanaId)
            ->where('work_order_actual_id', $workOrderActual->id)
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
     * Get available WorkOrderPlanning for creating actual
     */
    public function getAvailablePlanning(Request $request)
    {
        $query = WorkOrderPlanning::query()
            ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
            ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
            ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('trx_work_order_actual')
                    ->whereColumn('trx_work_order_actual.work_order_planning_id', 'trx_work_order_planning.id');
            })
            ->addSelect([
                'trx_work_order_planning.*',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
                'trx_sales_order.nomor_so'
            ]);

        $data = $query->get();
        return $this->successResponse($data);
    }
}
