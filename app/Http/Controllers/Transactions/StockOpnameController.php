<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Transactions\StockOpname;
use App\Models\Transactions\StockOpnameDetail;
use App\Http\Controllers\MasterData\ItemBarangController;

class StockOpnameController extends Controller
{
    use ApiFilterTrait;

    protected $itemBarangController;

    public function __construct(ItemBarangController $itemBarangController)
    {
        $this->itemBarangController = $itemBarangController;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = StockOpname::with(['picUser', 'gudang', 'stockOpnameDetails.itemBarang']);
        $query = $this->applyFilter($query, $request, []);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->successResponse(null, 'Data berhasil diambil');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pic_user_id' => 'required|exists:users,id',
            'gudang_id' => 'required|exists:ref_gudang,id',
            'catatan' => 'nullable|string',
            'should_freeze' => 'nullable|boolean'
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            // Handle freeze/unfreeze items sebelum create stock opname
            $shouldFreeze = $request->input('should_freeze', false);
            $gudangId = $request->gudang_id;

            if ($shouldFreeze) {
                // Freeze items
                $freezeRequest = new Request(['gudang_id' => $gudangId]);
                $freezeResponse = $this->itemBarangController->freezeItems($freezeRequest);
                
                // Check if freeze failed
                if (method_exists($freezeResponse, 'getStatusCode') && $freezeResponse->getStatusCode() !== 200) {
                    DB::rollBack();
                    $freezeData = $freezeResponse->getData();
                    $message = isset($freezeData->message) ? $freezeData->message : 'Gagal membekukan barang';
                    return $this->errorResponse($message, $freezeResponse->getStatusCode());
                }
            } else {
                // Unfreeze items
                $unfreezeRequest = new Request(['gudang_id' => $gudangId]);
                $unfreezeResponse = $this->itemBarangController->unfreezeItems($unfreezeRequest);
                
                // Check if unfreeze failed
                if (method_exists($unfreezeResponse, 'getStatusCode') && $unfreezeResponse->getStatusCode() !== 200) {
                    DB::rollBack();
                    $unfreezeData = $unfreezeResponse->getData();
                    $message = isset($unfreezeData->message) ? $unfreezeData->message : 'Gagal melepas status beku barang';
                    return $this->errorResponse($message, $unfreezeResponse->getStatusCode());
                }
            }

            // Create header
            $stockOpname = StockOpname::create([
                'pic_user_id' => $request->pic_user_id,
                'gudang_id' => $request->gudang_id,
                'catatan' => $request->catatan,
                'status' => 'active', // Set status default ke 'active' saat dibuat
            ]);

            DB::commit();

            $stockOpname->load(['picUser', 'gudang', 'stockOpnameDetails.itemBarang']);
            return $this->successResponse($stockOpname, 'Data berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menyimpan data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = StockOpname::with(['picUser', 'gudang', 'stockOpnameDetails.itemBarang'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = StockOpname::with(['picUser', 'gudang', 'stockOpnameDetails.itemBarang'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $stockOpname = StockOpname::find($id);
        if (!$stockOpname) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            'pic_user_id' => 'required|exists:users,id',
            'gudang_id' => 'required|exists:ref_gudang,id',
            'catatan' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*.item_barang_id' => 'required|exists:ref_item_barang,id',
            'details.*.status' => 'required|in:lebih,kurang,sama',
            'details.*.stok_buku' => 'required|integer',
            'details.*.stok_asli' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            // Update header
            $stockOpname->update([
                'pic_user_id' => $request->pic_user_id,
                'gudang_id' => $request->gudang_id,
                'catatan' => $request->catatan,
            ]);

            // Delete existing details
            StockOpnameDetail::where('stock_opname_id', $stockOpname->id)->delete();

            // Create new details
            foreach ($request->details as $detail) {
                StockOpnameDetail::create([
                    'stock_opname_id' => $stockOpname->id,
                    'item_barang_id' => $detail['item_barang_id'],
                    'status' => $detail['status'],
                    'stok_buku' => $detail['stok_buku'],
                    'stok_asli' => $detail['stok_asli'],
                ]);
            }

            DB::commit();

            $stockOpname->load(['picUser', 'gudang', 'stockOpnameDetails.itemBarang']);
            return $this->successResponse($stockOpname, 'Data berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal mengupdate data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = StockOpname::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil dihapus');
    }

    public function restore(string $id)
    {
        $data = StockOpname::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->restore();
        $data->load(['picUser', 'gudang', 'stockOpnameDetails.itemBarang']);
        return $this->successResponse($data, 'Data berhasil direstore');
    }

    public function forceDelete(string $id)
    {
        $data = StockOpname::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil dihapus permanen');
    }

    /**
     * Cancel stock opname
     */
    public function cancel(string $id)
    {
        $stockOpname = StockOpname::find($id);
        if (!$stockOpname) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        // Validasi bahwa stock opname belum cancelled atau completed
        if ($stockOpname->status === 'cancelled') {
            return $this->errorResponse('Stock opname sudah dibatalkan', 422);
        }

        if ($stockOpname->status === 'completed') {
            return $this->errorResponse('Stock opname yang sudah selesai tidak dapat dibatalkan', 422);
        }

        try {
            DB::beginTransaction();

            // Unfreeze items jika ada yang di-freeze
            $gudangId = $stockOpname->gudang_id;
            $unfreezeRequest = new Request(['gudang_id' => $gudangId]);
            $unfreezeResponse = $this->itemBarangController->unfreezeItems($unfreezeRequest);
            
            // Check if unfreeze failed
            if (method_exists($unfreezeResponse, 'getStatusCode') && $unfreezeResponse->getStatusCode() !== 200) {
                DB::rollBack();
                $unfreezeData = $unfreezeResponse->getData();
                $message = isset($unfreezeData->message) ? $unfreezeData->message : 'Gagal melepas status beku barang';
                return $this->errorResponse($message, $unfreezeResponse->getStatusCode());
            }

            // Update status menjadi cancelled
            $stockOpname->update([
                'status' => 'cancelled',
            ]);

            DB::commit();

            $stockOpname->load(['picUser', 'gudang', 'stockOpnameDetails.itemBarang']);
            return $this->successResponse($stockOpname, 'Stock opname berhasil dibatalkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal membatalkan stock opname: ' . $e->getMessage(), 500);
        }
    }
}
