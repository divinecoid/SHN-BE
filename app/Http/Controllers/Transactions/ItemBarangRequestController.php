<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transactions\ItemBarangRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\ApiFilterTrait;
use App\Http\Controllers\MasterData\DocumentSequenceController;

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
            'approvedBy:id,name,username'
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

        try {
            DB::beginTransaction();

            // Generate nomor request
            $nomorRequest = DocumentSequenceController::generateSequence('ITEM_REQUEST');

            $itemRequest = ItemBarangRequest::create([
                'nomor_request' => $nomorRequest,
                'nama_item_barang' => $request->nama_item_barang,
                'jenis_barang_id' => $request->jenis_barang_id,
                'bentuk_barang_id' => $request->bentuk_barang_id,
                'grade_barang_id' => $request->grade_barang_id,
                'panjang' => $request->panjang,
                'lebar' => $request->lebar,
                'tebal' => $request->tebal,
                'quantity' => $request->quantity,
                'keterangan' => $request->keterangan,
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
            'approvedBy:id,name,username'
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
            'keterangan' => 'nullable|string'
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
                'keterangan' => $request->keterangan
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
            'approval_notes' => 'nullable|string'
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
            $itemRequest->update([
                'status' => 'approved',
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

            return $this->successResponse($itemRequest, 'Request item barang berhasil disetujui');

        } catch (\Exception $e) {
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
