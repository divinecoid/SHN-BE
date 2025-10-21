<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\Transactions\PenerimaanBarang;
use App\Models\Transactions\PenerimaanBarangDetail;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\Gudang;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Transactions\PurchaseOrderItem;
use App\Http\Traits\ApiFilterTrait;
use App\Helpers\FileHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PenerimaanBarangController extends Controller
{
    use ApiFilterTrait;

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = PenerimaanBarang::with(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']);
        $query = $this->applyFilter($query, $request, ['catatan']);
        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($item) {
            $arrayItem = $item->toArray();
            $arrayItem['created_at'] = $item->created_at;
            return $arrayItem;
        });
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asal_penerimaan' => 'required|in:purchaseorder,stockmutation',
            'nomor_po' => 'nullable|string',
            'nomor_mutasi' => 'nullable|string',
            'gudang_id' => 'required|exists:ref_gudang,id',
            'catatan' => 'nullable|string',
            'bukti_foto' => 'nullable|string',
            'detail_barang' => 'required|array|min:1',
            'detail_barang.*.id' => 'required|integer',
            'detail_barang.*.kode' => 'required|string',
            'detail_barang.*.nama_item' => 'required|string',
            'detail_barang.*.ukuran' => 'required|string',
            'detail_barang.*.qty' => 'required|numeric|min:0',
            'detail_barang.*.status_scan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        // Conditional validation based on asal_penerimaan
        if ($request->asal_penerimaan === 'purchaseorder') {
            $validator = Validator::make($request->all(), [
                'nomor_po' => 'required|string',
                'nomor_mutasi' => 'nullable|prohibited',
            ]);
        } elseif ($request->asal_penerimaan === 'stockmutation') {
            $validator = Validator::make($request->all(), [
                'nomor_mutasi' => 'required|string',
                'nomor_po' => 'nullable|prohibited',
            ]);
        }

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            // Find purchase order or stock mutation based on asal_penerimaan
            $id_purchase_order = null;
            $id_stock_mutation = null;

            if ($request->asal_penerimaan === 'purchaseorder') {
                $purchaseOrder = \App\Models\Transactions\PurchaseOrder::where('nomor_po', $request->nomor_po)->first();
                if (!$purchaseOrder) {
                    throw new \Exception("Purchase Order dengan nomor {$request->nomor_po} tidak ditemukan");
                }
                $id_purchase_order = $purchaseOrder->id;
            } elseif ($request->asal_penerimaan === 'stockmutation') {
                $stockMutation = \App\Models\Transactions\StockMutation::where('nomor_mutasi', $request->nomor_mutasi)->first();
                if (!$stockMutation) {
                    throw new \Exception("Stock Mutation dengan nomor {$request->nomor_mutasi} tidak ditemukan");
                }
                $id_stock_mutation = $stockMutation->id;
            }

            // Create penerimaan barang record
            $penerimaanBarang = PenerimaanBarang::create([
                'origin' => $request->asal_penerimaan,
                'id_purchase_order' => $id_purchase_order,
                'id_stock_mutation' => $id_stock_mutation,
                'id_gudang' => $request->gudang_id,
                'catatan' => $request->catatan,
                'url_foto' => null
            ]);

            // Update status based on origin
            if ($request->asal_penerimaan === 'purchaseorder' && $purchaseOrder) {
                $purchaseOrder->update(['status' => 'received']);
                $purchaseOrderItems = PurchaseOrderItem::where('purchase_order_id', $purchaseOrder->id)->get();
                foreach ($purchaseOrderItems as $purchaseOrderItem) {
                    $itemBarang = ItemBarang::find($purchaseOrderItem->id_item_barang);
                    $itemBarang->is_onprogress_po = false;
                    $itemBarang->save();
                }
            } elseif ($request->asal_penerimaan === 'stockmutation' && $stockMutation) {
                $stockMutation->update(['status' => 'accepted']);
            }

            // Simpan bukti_foto jika dikirim (base64)
            $buktiFotoBase64 = $request->input('bukti_foto');
            if (!empty($buktiFotoBase64)) {
                $folderPath = 'penerimaan-barang/' . $penerimaanBarang->id;
                $fileName = 'bukti_foto';
                $result = FileHelper::saveBase64AsJpg($buktiFotoBase64, $folderPath, $fileName);
                if ($result['success'] ?? false) {
                    $penerimaanBarang->url_foto = $result['data']['path'] ?? null;
                    $penerimaanBarang->save();
                } else {
                    Log::error('Failed to save bukti_foto: ' . ($result['message'] ?? 'Unknown error'));
                    throw new \Exception('Gagal menyimpan bukti foto: ' . ($result['message'] ?? 'Unknown error'));
                }
            }

            // Create details
            foreach ($request->detail_barang as $detail) {
                // Find item barang by id
                $itemBarang = ItemBarang::find($detail['id']);
                if (!$itemBarang) {
                    throw new \Exception("Item barang dengan ID {$detail['id']} tidak ditemukan");
                }

                // For now, we'll use the gudang_id as the rak_id since the new structure doesn't specify rak
                // You may need to adjust this based on your business logic
                $rak_id = $request->gudang_id; // This might need to be adjusted based on your requirements

                PenerimaanBarangDetail::create([
                    'id_penerimaan_barang' => $penerimaanBarang->id,
                    'id_item_barang' => $detail['id'],
                    'id_rak' => $rak_id,
                    'qty' => $detail['qty'],
                    'id_purchase_order_item' => null, // This might need to be found based on your logic
                    'id_stock_mutation_detail' => null, // This might need to be found based on your logic
                ]);
            }

            DB::commit();

            return $this->successResponse($penerimaanBarang->load(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']), 'Data penerimaan barang berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in PenerimaanBarang store: ' . $e->getMessage());
            return $this->errorResponse('Terjadi kesalahan saat menyimpan data: ' . $e->getMessage(), 500);
        }
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
            'foto_bukti' => 'nullable|string',
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

        try {
            DB::beginTransaction();

            $data->update($request->only([
                'origin',
                'id_purchase_order',
                'id_stock_mutation',
                'id_gudang',
                'catatan',
                'url_foto'
            ]));

            // Simpan foto_bukti jika dikirim (base64), menggunakan logic seperti WorkOrderActualController
            $fotoBuktiBase64 = $request->input('foto_bukti');
            if (!empty($fotoBuktiBase64)) {
                $folderPath = 'penerimaan-barang/' . $data->id;
                $fileName = 'foto_bukti';
                $result = FileHelper::saveBase64AsJpg($fotoBuktiBase64, $folderPath, $fileName);
                if ($result['success'] ?? false) {
                    $data->url_foto = $result['data']['path'] ?? null;
                    $data->save();
                } else {
                    Log::error('Failed to save foto_bukti: ' . ($result['message'] ?? 'Unknown error'));
                    throw new \Exception('Gagal menyimpan foto bukti: ' . ($result['message'] ?? 'Unknown error'));
                }
            }

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

            DB::commit();

            return $this->successResponse($data->load(['purchaseOrder', 'stockMutation', 'gudang', 'penerimaanBarangDetails']), 'Data penerimaan barang berhasil diupdate');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error in PenerimaanBarang update: ' . $e->getMessage());
            return $this->errorResponse('Terjadi kesalahan saat mengupdate data: ' . $e->getMessage(), 500);
        }
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
