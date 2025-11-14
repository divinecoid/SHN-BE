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
        // Base query (remove salesOrderItems eager load, just count)
        $query = SalesOrder::with([
            'pelanggan',
            'gudang',
            'deleteRequestedBy',
            'deleteApprovedBy'
        ])->withCount('salesOrderItems');

        // Generic search/sort
        $query = $this->applyFilter($query, $request, ['nomor_so', 'syarat_pembayaran', 'status']);

        // Optional date range filter by tanggal_so
        $start = $request->input('date_start');
        $end = $request->input('date_end');
        if ($start && $end) {
            $query->whereBetween('tanggal_so', [$start, $end]);
        } elseif ($start) {
            $query->whereDate('tanggal_so', '>=', $start);
        } elseif ($end) {
            $query->whereDate('tanggal_so', '<=', $end);
        }

        // Conditional pagination: paginate only if per_page or page provided; otherwise return all on a single page
        $shouldPaginate = $request->filled('per_page') || $request->filled('page');
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        if (!$shouldPaginate) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($item) {
            $arrayItem = $item->toArray();

            // Hapus detail item sales_order_items, tampilkan hanya items_count
            unset($arrayItem['sales_order_items']);
            $arrayItem['items_count'] = $item->sales_order_items_count ?? 0;

            return $arrayItem;
        });
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
                    'jenis_potongan' => $item['jenis_potongan'],
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
                    'jenis_potongan' => $item['jenis_potongan'],
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
        $query = SalesOrder::with(['pelanggan:id,nama_pelanggan', 'gudang:id,kode,nama_gudang'])
            ->withCount('salesOrderItems');
        
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
        $items = collect($data->items())->map(function ($item) {
            $arrayItem = $item->toArray();
            $arrayItem['items_count'] = $item->sales_order_items_count ?? 0;
            return $arrayItem;
        });
        
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
     * Report endpoint: list Sales Orders with summary and basic filters
     *
     * Query params:
     * - per_page, search, sort/sort_by/order (via ApiFilterTrait)
     * - tanggal_mulai, tanggal_akhir, pelanggan_id, gudang_id, status
     * - min_total, max_total (filter by total_harga_so)
     */
    public function report(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));

        // Base query with lightweight relations and items count
        $query = SalesOrder::with([
            'pelanggan:id,nama_pelanggan',
            'gudang:id,kode,nama_gudang',
        ])->withCount('salesOrderItems');

        // Apply generic search/sort
        $query = $this->applyFilter($query, $request, ['nomor_so', 'syarat_pembayaran', 'status']);

        // Date range filters
        if ($request->filled('tanggal_mulai')) {
            $query->where('tanggal_so', '>=', $request->tanggal_mulai);
        }
        if ($request->filled('tanggal_akhir')) {
            $query->where('tanggal_so', '<=', $request->tanggal_akhir);
        }

        // Entity filters
        if ($request->filled('pelanggan_id')) {
            $query->where('pelanggan_id', $request->pelanggan_id);
        }
        if ($request->filled('gudang_id')) {
            $query->where('gudang_id', $request->gudang_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Total range filters
        if ($request->filled('min_total')) {
            $query->where('total_harga_so', '>=', (float)$request->min_total);
        }
        if ($request->filled('max_total')) {
            $query->where('total_harga_so', '<=', (float)$request->max_total);
        }

        // Default sort by latest date
        if (!$request->filled('sort') && !$request->filled('sort_by')) {
            $query->orderBy('tanggal_so', 'desc');
        }

        // Paginated data
        $data = $query->paginate($perPage);
        $items = collect($data->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'nomor_so' => $item->nomor_so,
                'tanggal_so' => $item->tanggal_so,
                'tanggal_pengiriman' => $item->tanggal_pengiriman,
                'status' => $item->status,
                'pelanggan' => [
                    'id' => $item->pelanggan->id ?? null,
                    'nama_pelanggan' => $item->pelanggan->nama_pelanggan ?? null,
                ],
                'gudang' => [
                    'id' => $item->gudang->id ?? null,
                    'kode' => $item->gudang->kode ?? null,
                    'nama_gudang' => $item->gudang->nama_gudang ?? null,
                ],
                'subtotal' => $item->subtotal,
                'total_diskon' => $item->total_diskon,
                'ppn_amount' => $item->ppn_amount,
                'total_harga_so' => $item->total_harga_so,
                'items_count' => $item->sales_order_items_count,
            ];
        });

        // Summary for filtered dataset (non-paginated)
        $summaryQuery = clone $query;
        $ids = (clone $summaryQuery)->pluck('id');
        $summary = [
            'orders_count' => $ids->count(),
            'items_count' => \App\Models\MasterData\SalesOrderItem::whereIn('sales_order_id', $ids)->count(),
            'subtotal_sum' => (clone $summaryQuery)->sum('subtotal'),
            'total_diskon_sum' => (clone $summaryQuery)->sum('total_diskon'),
            'ppn_amount_sum' => (clone $summaryQuery)->sum('ppn_amount'),
            'total_amount_sum' => (clone $summaryQuery)->sum('total_harga_so'),
        ];

        $response = $this->paginateResponse($data, $items);
        $response['summary'] = $summary;
        return response()->json($response);
    }

    /**
     * Return Sales Order items with remaining quantity after previous WO Planning allocations.
     * Query params:
     * - sales_order_id (required): ID Sales Order
     * - per_page (optional): pagination size
     * - include_zero (optional boolean): include items where sisa_qty <= 0
     */
    public function salesOrderForWOPlanning(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales_order_id' => 'required|exists:trx_sales_order,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $salesOrderId = (int) $request->input('sales_order_id');

        $plannedAgg = DB::table('trx_work_order_planning_item as wopi')
            ->select('wopi.sales_order_item_id', DB::raw('COALESCE(SUM(wopi.qty), 0) as qty_ref'))
            ->whereNull('wopi.deleted_at')
            ->groupBy('wopi.sales_order_item_id');

        $query = DB::table('trx_sales_order_item as soi')
            ->leftJoinSub($plannedAgg, 'wo_sum', function ($join) {
                $join->on('wo_sum.sales_order_item_id', '=', 'soi.id');
            })
            ->select([
                'soi.id',
                'soi.sales_order_id',
                'soi.panjang',
                'soi.lebar',
                'soi.tebal',
                'soi.qty as qty_so',
                'soi.jenis_barang_id',
                'soi.bentuk_barang_id',
                'soi.grade_barang_id',
                'soi.harga',
                'soi.satuan',
                'soi.jenis_potongan',
                'soi.diskon',
                'soi.catatan',
                DB::raw('COALESCE(wo_sum.qty_ref, 0) as qty_ref'),
                DB::raw('(soi.qty - COALESCE(wo_sum.qty_ref, 0)) as sisa_qty'),
            ])
            ->where('soi.sales_order_id', $salesOrderId)
            ->whereNull('soi.deleted_at');

        $query->having('sisa_qty', '>', 0);

        $query->orderBy('soi.id', 'asc');

        $itemsAll = $query->get();
        $total = $itemsAll->count();
        $perPage = $total > 0 ? $total : 1;
        $page = 1;
        $pagedItems = $itemsAll->slice(0, $perPage)->values();

        $enriched = $pagedItems->map(function ($row) {
            $item = SalesOrderItem::with(['jenisBarang:id,kode,nama_jenis', 'bentukBarang:id,kode,nama_bentuk,dimensi', 'gradeBarang:id,kode,nama'])
                ->find($row->id);
            return [
                'id' => $row->id,
                'sales_order_id' => $row->sales_order_id,
                'panjang' => $row->panjang,
                'lebar' => $row->lebar,
                'tebal' => $row->tebal,
                'qty_so' => (int) $row->qty_so,
                'qty_ref' => (int) $row->qty_ref,
                'sisa_qty' => max((int) $row->sisa_qty, 0),
                'jenis_barang' => $item?->jenisBarang,
                'bentuk_barang' => $item?->bentukBarang,
                'grade_barang' => $item?->gradeBarang,
                'harga' => $row->harga,
                'satuan' => $row->satuan,
                'jenis_potongan' => $row->jenis_potongan,
                'diskon' => $row->diskon,
                'catatan' => $row->catatan,
            ];
        });

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $enriched,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return response()->json($this->paginateResponse($paginator, $enriched));
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