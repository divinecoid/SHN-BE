<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\Transactions\PenerimaanBarang;
use App\Models\Transactions\PenerimaanBarangDetail;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\Gudang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;

class PenerimaanBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'origin' => 'required|in:purchaseorder,stockmutation',
            'id_gudang' => 'required|exists:ref_gudang,id',
            'catatan' => 'nullable|string',
            'url_foto' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.id_item_barang' => 'required|exists:ref_item_barang,id',
            'details.*.id_rak' => 'required|exists:ref_gudang,id',
            'details.*.qty' => 'required|numeric|min:0',
            'details.*.id_purchase_order_item' => 'nullable|exists:trx_purchase_order_item,id',
            'details.*.id_stock_mutation_detail' => 'nullable|exists:trx_stock_mutation_detail,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Conditional validation based on origin
        if ($request->origin === 'purchaseorder') {
            $validator = Validator::make($request->all(), [
                'id_purchase_order' => 'required|exists:trx_purchase_order,id',
                'id_stock_mutation' => 'nullable|prohibited',
            ]);
        } elseif ($request->origin === 'stockmutation') {
            $validator = Validator::make($request->all(), [
                'id_stock_mutation' => 'required|exists:trx_stock_mutation,id',
                'id_purchase_order' => 'nullable|prohibited',
            ]);
        }

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Validasi bahwa rak adalah child dari gudang untuk setiap detail
        $gudang = Gudang::find($request->id_gudang);
        foreach ($request->details as $detail) {
            $rak = Gudang::find($detail['id_rak']);
            if (!$rak->ancestors->contains('id', $gudang->id)) {
                return $this->errorResponse('Rak harus berada di dalam gudang yang dipilih', 422);
            }
        }

        $penerimaanBarang = PenerimaanBarang::create($request->only([
            'origin',
            'id_purchase_order',
            'id_stock_mutation',
            'id_gudang',
            'catatan',
            'url_foto'
        ]));

        // Create details
        foreach ($request->details as $detail) {
            PenerimaanBarangDetail::create([
                'id_penerimaan_barang' => $penerimaanBarang->id,
                'id_item_barang' => $detail['id_item_barang'],
                'id_rak' => $detail['id_rak'],
                'qty' => $detail['qty'],
                'id_purchase_order_item' => $detail['id_purchase_order_item'] ?? null,
                'id_stock_mutation_detail' => $detail['id_stock_mutation_detail'] ?? null,
            ]);
        }

        return $this->successResponse($penerimaanBarang->load(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']), 'Data penerimaan barang berhasil ditambahkan');
    }

    public function show($id)
    {
        $data = PenerimaanBarang::with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $data = PenerimaanBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'origin' => 'required|in:purchaseorder,stockmutation',
            'id_gudang' => 'required|exists:ref_gudang,id',
            'catatan' => 'nullable|string',
            'url_foto' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.id_item_barang' => 'required|exists:ref_item_barang,id',
            'details.*.id_rak' => 'required|exists:ref_gudang,id',
            'details.*.qty' => 'required|numeric|min:0',
            'details.*.id_purchase_order_item' => 'nullable|exists:trx_purchase_order_item,id',
            'details.*.id_stock_mutation_detail' => 'nullable|exists:trx_stock_mutation_detail,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Conditional validation based on origin
        if ($request->origin === 'purchaseorder') {
            $validator = Validator::make($request->all(), [
                'id_purchase_order' => 'required|exists:trx_purchase_order,id',
                'id_stock_mutation' => 'nullable|prohibited',
            ]);
        } elseif ($request->origin === 'stockmutation') {
            $validator = Validator::make($request->all(), [
                'id_stock_mutation' => 'required|exists:trx_stock_mutation,id',
                'id_purchase_order' => 'nullable|prohibited',
            ]);
        }

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Validasi bahwa rak adalah child dari gudang untuk setiap detail
        $gudang = Gudang::find($request->id_gudang);
        foreach ($request->details as $detail) {
            $rak = Gudang::find($detail['id_rak']);
            if (!$rak->ancestors->contains('id', $gudang->id)) {
                return $this->errorResponse('Rak harus berada di dalam gudang yang dipilih', 422);
            }
        }

        $data->update($request->only([
            'origin',
            'id_purchase_order',
            'id_stock_mutation',
            'id_gudang',
            'catatan',
            'url_foto'
        ]));

        // Delete existing details and create new ones
        $data->penerimaanBarangDetails()->delete();
        foreach ($request->details as $detail) {
            PenerimaanBarangDetail::create([
                'id_penerimaan_barang' => $data->id,
                'id_item_barang' => $detail['id_item_barang'],
                'id_rak' => $detail['id_rak'],
                'qty' => $detail['qty'],
                'id_purchase_order_item' => $detail['id_purchase_order_item'] ?? null,
                'id_stock_mutation_detail' => $detail['id_stock_mutation_detail'] ?? null,
            ]);
        }

        return $this->successResponse($data->load(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']), 'Data penerimaan barang berhasil diupdate');
    }

    public function softDelete($id)
    {
        $data = PenerimaanBarang::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil di-soft delete');
    }

    public function restore($id)
    {
        $data = PenerimaanBarang::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak soft deleted', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil di-restore');
    }

    public function forceDelete($id)
    {
        $data = PenerimaanBarang::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil di-force delete');
    }

    public function indexWithTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::withTrashed()->with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function indexTrashed(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::onlyTrashed()->with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get penerimaan barang by item barang
     */
    public function getByItemBarang($idItemBarang, Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::whereHas('penerimaanBarangDetails', function($q) use ($idItemBarang) {
            $q->where('id_item_barang', $idItemBarang);
        })->with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get penerimaan barang by gudang
     */
    public function getByGudang($idGudang, Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::where('id_gudang', $idGudang)
            ->with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get penerimaan barang by rak
     */
    public function getByRak($idRak, Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::whereHas('penerimaanBarangDetails', function($q) use ($idRak) {
            $q->where('id_rak', $idRak);
        })->with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }
}
