<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Models\MasterData\SalesOrder;
use App\Models\MasterData\SalesOrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Http\Controllers\MasterData\DocumentSequenceController;

class SalesOrderController extends Controller
{
    use ApiFilterTrait;
    
    protected $documentSequenceController;
    
    public function __construct()
    {
        $this->documentSequenceController = new DocumentSequenceController();
    }

    public function index(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = SalesOrder::with(['salesOrderItems.jenisBarang', 'salesOrderItems.bentukBarang', 'salesOrderItems.gradeBarang', 'pelanggan', 'gudang', 'deleteRequestedBy', 'deleteApprovedBy']);
        $query = $this->applyFilter($query, $request, ['nomor_so', 'syarat_pembayaran', 'status']);
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Header validation
            'tanggal_so' => 'required|date',
            'tanggal_pengiriman' => 'required|date',
            'syarat_pembayaran' => 'required|string',
            'gudang_id' => 'required|exists:ref_gudang,id',
            'pelanggan_id' => 'required|exists:ref_pelanggan,id',
            'handover_method' => 'nullable|string',
            
            // Summary validation
            'subtotal' => 'required|numeric|min:0',
            'total_diskon' => 'required|numeric|min:0',
            'ppn_percent' => 'required|numeric|min:0|max:100',
            'ppn_amount' => 'required|numeric|min:0',
            'total_harga_so' => 'required|numeric|min:0',
            
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.panjang' => 'required|numeric|min:0',
            'items.*.lebar' => 'required|numeric|min:0',
            'items.*.tebal' => 'nullable|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'items.*.bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'items.*.grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.satuan' => 'required|string',
            'items.*.jenis_potongan' => 'required|string|in:utuh,potongan',
            'items.*.diskon' => 'nullable|numeric|min:0|max:100',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Generate nomor_so menggunakan DocumentSequenceController
            $nomorSoResponse = $this->documentSequenceController->generateDocumentSequence('so');
            if ($nomorSoResponse->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor SO', 500);
            }
            $nomorSo = $nomorSoResponse->getData()->data;
            
            // Create sales order header
            $salesOrder = SalesOrder::create(array_merge($request->only([
                'nomor_so',
                'tanggal_so',
                'tanggal_pengiriman',
                'syarat_pembayaran',
                'gudang_id',
                'pelanggan_id',
                'subtotal',
                'total_diskon',
                'ppn_percent',
                'ppn_amount',
                'total_harga_so',
                'handover_method',
            ]), [
                'handover_method' => $request->handover_method ?? 'pickup'
            ]));

            // Create sales order items
            foreach ($request->items as $item) {
                $salesOrder->salesOrderItems()->create([
                    'panjang' => $item['panjang'],
                    'lebar' => $item['lebar'],
                    'tebal' => $item['tebal'] ?? null,
                    'qty' => $item['qty'],
                    'jenis_barang_id' => $item['jenis_barang_id'],
                    'bentuk_barang_id' => $item['bentuk_barang_id'],
                    'grade_barang_id' => $item['grade_barang_id'],
                    'harga' => $item['harga'],
                    'satuan' => $item['satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            // Update sequence counter setelah berhasil create SalesOrder
            $this->documentSequenceController->increaseSequence('so');

            DB::commit();

            // Load the created data with relationships
            $salesOrder->load('salesOrderItems.jenisBarang', 'salesOrderItems.bentukBarang', 'salesOrderItems.gradeBarang', 'pelanggan', 'gudang');

            return $this->successResponse($salesOrder, 'Sales Order berhasil ditambahkan');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Sales Order: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        $data = SalesOrder::with(['salesOrderItems.jenisBarang', 'salesOrderItems.bentukBarang', 'salesOrderItems.gradeBarang', 'pelanggan', 'gudang'])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        return $this->successResponse($data);
    }

    public function update(Request $request, $id)
    {
        $salesOrder = SalesOrder::find($id);
        if (!$salesOrder) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        $validator = Validator::make($request->all(), [
            // Header validation
            'nomor_so' => 'required|string|unique:trx_sales_order,nomor_so,' . $id,
            'tanggal_so' => 'required|date',
            'tanggal_pengiriman' => 'required|date',
            'syarat_pembayaran' => 'required|string',
            'gudang_id' => 'required|exists:ref_gudang,id',
            'pelanggan_id' => 'required|exists:ref_pelanggan,id',
            'handover_method' => 'nullable|string',
            // Summary validation
            'subtotal' => 'required|numeric|min:0',
            'total_diskon' => 'required|numeric|min:0',
            'ppn_percent' => 'required|numeric|min:0|max:100',
            'ppn_amount' => 'required|numeric|min:0',
            'total_harga_so' => 'required|numeric|min:0',
            
            // Items validation
            'items' => 'required|array|min:1',
            'items.*.panjang' => 'required|numeric|min:0',
            'items.*.lebar' => 'required|numeric|min:0',
            'items.*.tebal' => 'nullable|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.jenis_barang_id' => 'required|exists:ref_jenis_barang,id',
            'items.*.bentuk_barang_id' => 'required|exists:ref_bentuk_barang,id',
            'items.*.grade_barang_id' => 'required|exists:ref_grade_barang,id',
            'items.*.harga' => 'required|numeric|min:0',
            'items.*.satuan' => 'required|string',
            'items.*.jenis_potongan' => 'required|string|in:utuh,potongan',
            'items.*.diskon' => 'nullable|numeric|min:0|max:100',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            // Update sales order header
            $salesOrder->update(array_merge($request->only([
                'nomor_so',
                'tanggal_so',
                'tanggal_pengiriman',
                'syarat_pembayaran',
                'gudang_id',
                'pelanggan_id',
                'subtotal',
                'total_diskon',
                'ppn_percent',
                'ppn_amount',
                'total_harga_so',
                'handover_method',
            ]), [
                'handover_method' => $request->handover_method ?? 'pickup'
            ]));

            // Delete existing items and create new ones
            $salesOrder->salesOrderItems()->delete();
            
            foreach ($request->items as $item) {
                $salesOrder->salesOrderItems()->create([
                    'panjang' => $item['panjang'],
                    'lebar' => $item['lebar'],
                    'tebal' => $item['tebal'] ?? null,
                    'qty' => $item['qty'],
                    'jenis_barang_id' => $item['jenis_barang_id'],
                    'bentuk_barang_id' => $item['bentuk_barang_id'],
                    'grade_barang_id' => $item['grade_barang_id'],
                    'harga' => $item['harga'],
                    'satuan' => $item['satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            DB::commit();

            // Load the updated data with relationships
            $salesOrder->load('salesOrderItems.jenisBarang', 'salesOrderItems.bentukBarang', 'salesOrderItems.gradeBarang', 'pelanggan', 'gudang');

            return $this->successResponse($salesOrder, 'Sales Order berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal memperbarui Sales Order: ' . $e->getMessage(), 500);
        }
    }

    public function softDelete($id)
    {
        $data = SalesOrder::find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->delete();
        return $this->successResponse(null, 'Data berhasil dihapus');
    }

    public function restore($id)
    {
        $data = SalesOrder::onlyTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan atau tidak dihapus', 404);
        }
        $data->restore();
        return $this->successResponse($data, 'Data berhasil dipulihkan');
    }

    public function forceDelete($id)
    {
        $data = SalesOrder::withTrashed()->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        $data->forceDelete();
        return $this->successResponse(null, 'Data berhasil dihapus permanen');
    }

    /**
     * Request delete sales order (user)
     */
    public function requestDelete(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'delete_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $salesOrder = SalesOrder::find($id);
        if (!$salesOrder) {
            return $this->errorResponse('Sales Order tidak ditemukan', 404);
        }

        if ($salesOrder->status !== 'active') {
            return $this->errorResponse('Sales Order tidak dapat dihapus', 400);
        }

        $salesOrder->update([
            'status' => 'delete_requested',
            'delete_requested_by' => auth()->id(),
            'delete_requested_at' => now(),
            'delete_reason' => $request->delete_reason,
        ]);

        return $this->successResponse($salesOrder, 'Request penghapusan Sales Order berhasil dikirim');
    }

    /**
     * Approve delete request (admin)
     */
    public function approveDelete($id)
    {
        $salesOrder = SalesOrder::where('status', 'delete_requested')->find($id);
        if (!$salesOrder) {
            return $this->errorResponse('Request penghapusan tidak ditemukan', 404);
        }

        $salesOrder->update([
            'status' => 'deleted',
            'delete_approved_by' => auth()->id(),
            'delete_approved_at' => now(),
        ]);

        // Soft delete the sales order
        $salesOrder->delete();

        return $this->successResponse(null, 'Request penghapusan Sales Order berhasil disetujui');
    }

    /**
     * Reject delete request (admin)
     */
    public function rejectDelete(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $salesOrder = SalesOrder::where('status', 'delete_requested')->find($id);
        if (!$salesOrder) {
            return $this->errorResponse('Request penghapusan tidak ditemukan', 404);
        }

        $salesOrder->update([
            'status' => 'active',
            'delete_rejection_reason' => $request->rejection_reason,
            'delete_requested_by' => null,
            'delete_requested_at' => null,
            'delete_reason' => null,
        ]);

        return $this->successResponse($salesOrder, 'Request penghapusan Sales Order berhasil ditolak');
    }

    /**
     * Get pending delete requests for approval (admin)
     */
    public function getPendingDeleteRequests(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        $query = SalesOrder::where('status', 'delete_requested')
            ->with(['salesOrderItems.jenisBarang', 'salesOrderItems.bentukBarang', 'salesOrderItems.gradeBarang', 'pelanggan', 'gudang', 'deleteRequestedBy'])
            ->orderBy('delete_requested_at', 'desc');
        
        // Apply search filter
        $query = $this->applyFilter($query, $request, ['nomor_so', 'syarat_pembayaran']);
        
        // Filter by date range for delete requests
        if ($request->has('delete_requested_from')) {
            $query->where('delete_requested_at', '>=', $request->input('delete_requested_from'));
        }
        
        if ($request->has('delete_requested_to')) {
            $query->where('delete_requested_at', '<=', $request->input('delete_requested_to'));
        }
        
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get Sales Order data with header attributes only (without complex relationships)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSalesOrderHeader(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        
        // Query Sales Order with minimal relationships (only pelanggan and gudang for basic info)
        $query = SalesOrder::with(['pelanggan:id,nama_pelanggan', 'gudang:id,kode,nama_gudang']);
        
        // Apply filters
        $query = $this->applyFilter($query, $request, ['nomor_so', 'syarat_pembayaran', 'status']);
        
        // Add date range filter if provided
        if ($request->filled('tanggal_mulai')) {
            $query->where('tanggal_so', '>=', $request->tanggal_mulai);
        }
        
        if ($request->filled('tanggal_akhir')) {
            $query->where('tanggal_so', '<=', $request->tanggal_akhir);
        }
        
        // Add pelanggan filter if provided
        if ($request->filled('pelanggan_id')) {
            $query->where('pelanggan_id', $request->pelanggan_id);
        }
        
        // Add gudang filter if provided
        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }
        
        // Order by latest first
        $query->orderBy('tanggal_so', 'desc');
        
        $data = $query->paginate($perPage);
        $items = collect($data->items());
        
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Get Sales Order by ID with header attributes only (without complex relationships)
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSalesOrderHeaderById($id)
    {
        $data = SalesOrder::with(['pelanggan:id,nama_pelanggan', 'gudang:id,kode,nama_gudang'])
            ->select([
                'id',
                'nomor_so',
                'tanggal_so',
                'tanggal_pengiriman',
                'syarat_pembayaran',
                'gudang_id',
                'pelanggan_id',
                'subtotal',
                'total_diskon',
                'ppn_percent',
                'ppn_amount',
                'total_harga_so',
                'handover_method',
                'status',
                'created_at',
                'updated_at'
            ])
            ->find($id);
            
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        return $this->successResponse($data);
    }

    /**
     * Cancel delete request (user)
     */
    public function cancelDeleteRequest($id)
    {
        $salesOrder = SalesOrder::where('status', 'delete_requested')
            ->where('delete_requested_by', auth()->id())
            ->find($id);

        if (!$salesOrder) {
            return $this->errorResponse('Request penghapusan tidak ditemukan', 404);
        }

        $salesOrder->update([
            'status' => 'active',
            'delete_requested_by' => null,
            'delete_requested_at' => null,
            'delete_reason' => null,
        ]);

        return $this->successResponse($salesOrder, 'Request penghapusan berhasil dibatalkan');
    }
}