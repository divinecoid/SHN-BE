<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transactions\ItemBarangRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiFilterTrait;
use App\Http\Controllers\MasterData\DocumentSequenceController;
use App\Models\MasterData\DocumentSequence;
use App\Models\MasterData\ItemBarang;

class ItemBarangRequestController extends Controller
{
    use ApiFilterTrait;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        
        $query = ItemBarangRequest::with([
            'jenisBarang',
            'bentukBarang', 
            'gradeBarang',
            'requestedBy:id,name,username',
            'approvedBy:id,name,username',
            'asalGudang:id,kode,nama_gudang',
            'tujuanGudang:id,kode,nama_gudang'
        ]);

        // Apply filters
        $query = $this->applyFilter($query, $request, [
            'status',
            'jenis_barang_id',
            'bentuk_barang_id',
            'grade_barang_id',
            'requested_by'
        ]);

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_request', 'like', "%{$search}%")
                  ->orWhere('nama_item_barang', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $items = collect($data->items());

        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Dua mode input: (A) via item_barang_id, atau (B) manual field
        if ($request->filled('item_barang_id')) {
            $validator = Validator::make($request->all(), [
                'item_barang_id' => 'required|exists:ref_item_barang,id',
                'quantity' => 'required|integer|min:1',
                'gudang_tujuan_id' => 'nullable|exists:ref_gudang,id',
                'gudang_asal_id' => 'nullable|exists:ref_gudang,id',
                'notes' => 'nullable|string',
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }
            $item = \App\Models\MasterData\ItemBarang::with(['jenisBarang','bentukBarang','gradeBarang'])->find($request->item_barang_id);
            if (!$item) {
                return $this->errorResponse('Item barang tidak ditemukan', 404);
            }
            // Map ke struktur request standar
            $request->merge([
                'nama_item_barang' => $item->nama_item_barang,
                'jenis_barang_id' => $item->jenis_barang_id,
                'bentuk_barang_id' => $item->bentuk_barang_id,
                'grade_barang_id' => $item->grade_barang_id,
                'panjang' => $item->panjang,
                'lebar' => $item->lebar,
                'tebal' => $item->tebal,
                'keterangan' => $request->input('notes'),
                'gudang_asal_id' => $request->input('gudang_asal_id', $item->gudang_id),
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'nama_item_barang' => 'required|string|max:255',
                'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
                'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
                'grade_barang_id' => 'required|exists:ref_grade_barang,id',
                'panjang' => 'nullable|numeric|min:0',
                'lebar' => 'nullable|numeric|min:0',
                'tebal' => 'nullable|numeric|min:0',
                'quantity' => 'required|integer|min:1',
                'keterangan' => 'nullable|string'
            ]);
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }
        }

        try {
            DB::beginTransaction();

            // Increment sequence terlebih dahulu agar unik, lalu format nomor RCP-ddmmyyyy-xxx
            // Generate nomor request memakai DocumentSequenceController
            $gen = (new DocumentSequenceController())->generateDocumentSequence('item_barang_request');
            if (method_exists($gen, 'getStatusCode') && $gen->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor request', 500);
            }
            $nomorRequest = $gen->getData()->data ?? null;
            if (!$nomorRequest) {
                return $this->errorResponse('Nomor request tidak tersedia', 500);
            }
            $inc = (new DocumentSequenceController())->increaseSequence('item_barang_request');
            if (method_exists($inc, 'getStatusCode') && $inc->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal update sequence request', 500);
            }

            $itemRequest = ItemBarangRequest::create([
                'nomor_request' => $nomorRequest,
                'item_barang_id' => $request->input('item_barang_id'),
                'nama_item_barang' => $request->nama_item_barang,
                'jenis_barang_id' => $request->jenis_barang_id,
                'bentuk_barang_id' => $request->bentuk_barang_id,
                'grade_barang_id' => $request->grade_barang_id,
                'panjang' => $request->panjang,
                'lebar' => $request->lebar,
                'tebal' => $request->tebal,
                'quantity' => $request->quantity,
                'keterangan' => $request->keterangan,
                'gudang_asal_id' => $request->input('gudang_asal_id'),
                'gudang_tujuan_id' => $request->input('gudang_tujuan_id'),
                'requested_by' => auth()->id(),
                'status' => 'pending'
            ]);

            $itemRequest->load([
                'jenisBarang',
                'bentukBarang',
                'gradeBarang',
                'requestedBy:id,name,username'
            ]);

            DB::commit();

            return $this->successResponse($itemRequest, 'Request item barang berhasil dibuat');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal membuat request item barang: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $itemRequest = ItemBarangRequest::with([
            'jenisBarang',
            'bentukBarang',
            'gradeBarang',
            'requestedBy:id,name,username',
            'approvedBy:id,name,username',
            'asalGudang:id,kode,nama_gudang',
            'tujuanGudang:id,kode,nama_gudang',
            'itemBarang:id,kode_barang,nama_item_barang,jenis_potongan,gudang_id',
            'itemBarang.gudang:id,kode,nama_gudang'
        ])->find($id);

        if (!$itemRequest) {
            return $this->errorResponse('Request item barang tidak ditemukan', 404);
        }

        return $this->successResponse($itemRequest, 'Request item barang ditemukan');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $itemRequest = ItemBarangRequest::find($id);

        if (!$itemRequest) {
            return $this->errorResponse('Request item barang tidak ditemukan', 404);
        }

        // Only allow updates if status is pending
        if ($itemRequest->status !== 'pending') {
            return $this->errorResponse('Hanya request dengan status pending yang dapat diubah', 422);
        }

        // Only allow the requester to update their own request
        if ($itemRequest->requested_by !== auth()->id()) {
            return $this->errorResponse('Anda tidak memiliki akses untuk mengubah request ini', 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_item_barang' => 'required|string|max:255',
            'jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'panjang' => 'nullable|numeric|min:0',
            'lebar' => 'nullable|numeric|min:0',
            'tebal' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'keterangan' => 'nullable|string',
            'gudang_asal_id' => 'nullable|exists:ref_gudang,id',
            'gudang_tujuan_id' => 'nullable|exists:ref_gudang,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $itemRequest->update([
                'nama_item_barang' => $request->nama_item_barang,
                'jenis_barang_id' => $request->jenis_barang_id,
                'bentuk_barang_id' => $request->bentuk_barang_id,
                'grade_barang_id' => $request->grade_barang_id,
                'panjang' => $request->panjang,
                'lebar' => $request->lebar,
                'tebal' => $request->tebal,
                'quantity' => $request->quantity,
                'keterangan' => $request->keterangan,
                'gudang_asal_id' => $request->input('gudang_asal_id'),
                'gudang_tujuan_id' => $request->input('gudang_tujuan_id')
            ]);

            $itemRequest->load([
                'jenisBarang',
                'bentukBarang',
                'gradeBarang',
                'requestedBy:id,name,username'
            ]);

            return $this->successResponse($itemRequest, 'Request item barang berhasil diperbarui');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui request item barang: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $itemRequest = ItemBarangRequest::find($id);

        if (!$itemRequest) {
            return $this->errorResponse('Request item barang tidak ditemukan', 404);
        }

        // Only allow deletion if status is pending
        if ($itemRequest->status !== 'pending') {
            return $this->errorResponse('Hanya request dengan status pending yang dapat dihapus', 422);
        }

        // Only allow the requester to delete their own request
        if ($itemRequest->requested_by !== auth()->id()) {
            return $this->errorResponse('Anda tidak memiliki akses untuk menghapus request ini', 403);
        }

        try {
            $itemRequest->delete();
            return $this->successResponse(null, 'Request item barang berhasil dihapus');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus request item barang: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get pending requests for approval
     */
    public function getPendingRequests(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        
        $query = ItemBarangRequest::with([
            'jenisBarang',
            'bentukBarang',
            'gradeBarang',
            'requestedBy:id,name,username'
        ])->pending();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_request', 'like', "%{$search}%")
                  ->orWhere('nama_item_barang', 'like', "%{$search}%")
                  ->orWhereHas('requestedBy', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $data = $query->orderBy('created_at', 'desc')->paginate($perPage);
        $items = collect($data->items());

        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Approve a request
     */
    public function approve(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string',
            'gudang_tujuan_id' => 'nullable|exists:ref_gudang,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $itemRequest = ItemBarangRequest::find($id);

        if (!$itemRequest) {
            return $this->errorResponse('Request item barang tidak ditemukan', 404);
        }

        if ($itemRequest->status !== 'pending') {
            return $this->errorResponse('Hanya request dengan status pending yang dapat disetujui', 422);
        }

        try {
            DB::beginTransaction();

            $destGudangId = $request->input('gudang_tujuan_id', $itemRequest->gudang_tujuan_id);
            if (!$destGudangId) {
                DB::rollBack();
                return $this->errorResponse('gudang_tujuan_id wajib diisi saat approve', 422);
            }

            if (!$itemRequest->item_barang_id) {
                DB::rollBack();
                return $this->errorResponse('Request ini tidak terhubung ke item barang asli (item_barang_id kosong)', 422);
            }

            $item = ItemBarang::find($itemRequest->item_barang_id);
            if (!$item) {
                DB::rollBack();
                return $this->errorResponse('Item barang sumber tidak ditemukan', 404);
            }

            $item->update([
                'gudang_id' => $destGudangId,
                'jenis_potongan' => 'potongan'
            ]);

            $itemRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes,
                'gudang_tujuan_id' => $destGudangId
            ]);

            DB::commit();

            $itemRequest->load([
                'jenisBarang',
                'bentukBarang',
                'gradeBarang',
                'requestedBy:id,name,username',
                'approvedBy:id,name,username',
                'asalGudang:id,kode,nama_gudang',
                'tujuanGudang:id,kode,nama_gudang'
            ]);

            return $this->successResponse([
                'request' => $itemRequest,
                'updated_item' => $item
            ], 'Request item barang disetujui, gudang dipindahkan dan jenis_potongan diubah menjadi potongan');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Gagal menyetujui request item barang: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Reject a request
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'approval_notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $itemRequest = ItemBarangRequest::find($id);

        if (!$itemRequest) {
            return $this->errorResponse('Request item barang tidak ditemukan', 404);
        }

        if ($itemRequest->status !== 'pending') {
            return $this->errorResponse('Hanya request dengan status pending yang dapat ditolak', 422);
        }

        try {
            $itemRequest->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            $itemRequest->load([
                'jenisBarang',
                'bentukBarang',
                'gradeBarang',
                'requestedBy:id,name,username',
                'approvedBy:id,name,username'
            ]);

            return $this->successResponse($itemRequest, 'Request item barang berhasil ditolak');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menolak request item barang: ' . $e->getMessage(), 500);
        }
    }
}
