<?php

namespace App\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use App\Models\MasterData\ItemBarang;
use App\Models\MasterData\SalesOrder;
use App\Models\Transactions\WorkOrderActual;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use App\Models\Transactions\WorkOrderActualItem;
use App\Models\Transactions\WorkOrderPlanningPelaksana;
use App\Models\Transactions\SaranPlatShaftDasar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Helpers\FileHelper;
use App\Http\Controllers\MasterData\DocumentSequenceController;

class WorkOrderPlanningController extends Controller
{
    use ApiFilterTrait;
    
    protected $documentSequenceController;
    
    public function __construct()
    {
        $this->documentSequenceController = new DocumentSequenceController();
    }

    public function index(Request $request)
    {
        $query = WorkOrderPlanning::query()
            ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
            ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
            ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id')
            ->addSelect([
                'trx_work_order_planning.*',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
                'trx_sales_order.nomor_so',
            ])
            ->withCount(['workOrderPlanningItems as count']);
        $query = $this->applyFilter($query, $request, ['sales_order.nomor_so', 'nomor_wo', 'tanggal_wo', 'prioritas', 'status']);

        // Optional date range filter based on created_at (WO Planning)
        $start = $request->input('date_start');
        $end = $request->input('date_end');
        if ($start && $end) {
            $query->whereBetween('trx_work_order_planning.created_at', [$start, $end]);
        } elseif ($start) {
            $query->where('trx_work_order_planning.created_at', '>=', $start);
        } elseif ($end) {
            $query->where('trx_work_order_planning.created_at', '<=', $end);
        }

        // Conditional pagination: paginate only if per_page or page provided; otherwise return all on a single page
        $shouldPaginate = $request->filled('per_page') || $request->filled('page');
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));
        if (!$shouldPaginate) {
            $total = (clone $query)->count();
            $perPage = $total > 0 ? $total : 1;
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());
        return response()->json($this->paginateResponse($data, $items));
    }

    /**
     * Report header Work Order Planning (atribut parent saja)
     */
    public function report(Request $request)
    {
        $perPage = (int)($request->input('per_page', $this->getPerPageDefault()));

        $query = WorkOrderPlanning::query()
            ->leftJoin('ref_pelanggan', 'trx_work_order_planning.id_pelanggan', '=', 'ref_pelanggan.id')
            ->leftJoin('ref_gudang', 'trx_work_order_planning.id_gudang', '=', 'ref_gudang.id')
            ->leftJoin('trx_sales_order', 'trx_work_order_planning.id_sales_order', '=', 'trx_sales_order.id')
            ->addSelect([
                'trx_work_order_planning.id',
                'trx_work_order_planning.wo_unique_id',
                'trx_work_order_planning.nomor_wo',
                'trx_work_order_planning.tanggal_wo',
                'trx_work_order_planning.id_sales_order',
                'trx_work_order_planning.id_pelanggan',
                'trx_work_order_planning.id_gudang',
                'trx_work_order_planning.id_pelaksana',
                'trx_work_order_planning.prioritas',
                'trx_work_order_planning.status',
                'trx_work_order_planning.handover_method',
                'trx_work_order_planning.created_at',
                'trx_work_order_planning.updated_at',
                'ref_pelanggan.nama_pelanggan',
                'ref_gudang.nama_gudang',
                'trx_sales_order.nomor_so',
            ]);

        // Generic search & sort
        $query = $this->applyFilter($query, $request, [
            'trx_work_order_planning.nomor_wo',
            'trx_sales_order.nomor_so',
            'ref_pelanggan.nama_pelanggan',
            'ref_gudang.nama_gudang',
            'trx_work_order_planning.status',
            'trx_work_order_planning.prioritas',
        ]);

        // Specific filters
        if ($request->filled('status')) {
            $query->where('trx_work_order_planning.status', $request->input('status'));
        }
        if ($request->filled('prioritas')) {
            $query->where('trx_work_order_planning.prioritas', $request->input('prioritas'));
        }
        if ($request->filled('id_pelanggan')) {
            $query->where('trx_work_order_planning.id_pelanggan', $request->input('id_pelanggan'));
        }
        if ($request->filled('id_gudang')) {
            $query->where('trx_work_order_planning.id_gudang', $request->input('id_gudang'));
        }
        if ($request->filled('nomor_wo')) {
            $query->where('trx_work_order_planning.nomor_wo', 'like', "%" . $request->input('nomor_wo') . "%");
        }
        if ($request->filled('nomor_so')) {
            $query->where('trx_sales_order.nomor_so', 'like', "%" . $request->input('nomor_so') . "%");
        }

        // Date range filter
        $start = $request->input('tanggal_wo_start');
        $end = $request->input('tanggal_wo_end');
        if ($start && $end) {
            $query->whereBetween('trx_work_order_planning.tanggal_wo', [$start, $end]);
        } elseif ($start) {
            $query->whereDate('trx_work_order_planning.tanggal_wo', '>=', $start);
        } elseif ($end) {
            $query->whereDate('trx_work_order_planning.tanggal_wo', '<=', $end);
        }

        // Default sort to avoid ambiguous 'id' with joins
        if (!$request->filled('sort') && !$request->filled('sort_by')) {
            $query->orderBy('trx_work_order_planning.tanggal_wo', 'desc');
        }

        $data = $query->paginate($perPage);
        $items = collect($data->items());

        return response()->json($this->paginateResponse($data, $items));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [ 
            'wo_unique_id' => 'required|string|unique:trx_work_order_planning,wo_unique_id',
            'id_sales_order' => 'required|exists:trx_sales_order,id',
            'id_pelanggan' => 'required|exists:ref_pelanggan,id',
            'id_gudang' => 'required|exists:ref_gudang,id',
            'status' => 'required|string',
            'tanggal_wo' => 'required|date',
            'prioritas' => 'required|string',
            'handover_method' => 'required|string|in:pickup,delivery',
            'items' => 'required|array',
            'items.*.wo_item_unique_id' => 'required|string|unique:trx_work_order_planning_item,wo_item_unique_id',
            'items.*.sales_order_item_id' => 'nullable|exists:trx_sales_order_item,id',
            'items.*.pelaksana' => 'nullable|array',
            'items.*.pelaksana.*.pelaksana_id' => 'required|exists:ref_pelaksana,id',
            'items.*.pelaksana.*.qty' => 'nullable|integer|min:0',
            'items.*.pelaksana.*.weight' => 'nullable|numeric|min:0',
            'items.*.pelaksana.*.tanggal' => 'nullable|date',
            'items.*.pelaksana.*.jam_mulai' => 'nullable|date_format:H:i',
            'items.*.pelaksana.*.jam_selesai' => 'nullable|date_format:H:i',
            'items.*.pelaksana.*.catatan' => 'nullable|string|max:500',
            'items.*.jenis_potongan' => 'nullable|in:utuh,potongan',
            'items.*.saran_plat_dasar' => 'nullable|array',
            'items.*.saran_plat_dasar.*.item_barang_id' => 'required|exists:ref_item_barang,id',
            'items.*.saran_plat_dasar.*.quantity' => 'nullable|numeric|min:0',
            'items.*.saran_plat_dasar.*.canvas_image' => 'nullable|string',
            'items.*.saran_plat_dasar.*.canvas_layout' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }
        DB::beginTransaction();
        try {
            // Generate nomor_wo menggunakan DocumentSequenceController
            $nomorWoResponse = $this->documentSequenceController->generateDocumentSequence('wo');
            if ($nomorWoResponse->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor WO', 500);
            }
            $nomorWo = $nomorWoResponse->getData()->data;
            
            // Generate wo_unique_id (bisa menggunakan UUID atau kombinasi lainnya)
            $woUniqueId = 'WO-' . uniqid();
            
            // Membuat header Work Order Planning
            $workOrderData = $request->only([
                'wo_unique_id',
                'tanggal_wo',
                'id_sales_order',
                'id_pelanggan',
                'id_gudang',
                'prioritas',
                'status',
                'handover_method',
            ]);
            $workOrderData['wo_unique_id'] = $woUniqueId;
            $workOrderData['nomor_wo'] = $nomorWo;
            $workOrderPlanning = WorkOrderPlanning::create($workOrderData);


            // Jika ada items, simpan items beserta relasi ke ref_jenis_barang, ref_bentuk_barang, ref_grade_barang
            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    $workOrderPlanningItem = WorkOrderPlanningItem::create([
                        'sales_order_item_id' => $item['sales_order_item_id'] ?? null,
                        'wo_item_unique_id' => $item['wo_item_unique_id'],
                        'work_order_planning_id' => $workOrderPlanning->id,
                        'qty' => $item['qty'] ?? 0,
                        'panjang' => $item['panjang'] ?? 0,
                        'lebar' => $item['lebar'] ?? 0,
                        'berat' => $item['berat'] ?? 0,
                        'tebal' => $item['tebal'] ?? 0,
                        'jenis_barang_id' => $item['jenis_barang_id'] ?? null,
                        'berat' => $item['berat'] ?? 0,
                        'satuan' => $item['satuan'] ?? 'PCS',
                        'diskon' => $item['diskon'] ?? 0,
                        'bentuk_barang_id' => $item['bentuk_barang_id'] ?? null,
                        'grade_barang_id' => $item['grade_barang_id'] ?? null,
                        'catatan' => $item['catatan'] ?? null,
                        'jenis_potongan' => $item['jenis_potongan'] ?? null,
                    ]);

                    // Insert pelaksana ke item jika ada
                    if (isset($item['pelaksana']) && is_array($item['pelaksana'])) {
                        foreach ($item['pelaksana'] as $pelaksanaData) {
                            WorkOrderPlanningPelaksana::create([
                                'wo_plan_item_id' => $workOrderPlanningItem->id,
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

                    // Insert saran plat dasar ke item jika ada
                    if (isset($item['saran_plat_dasar']) && is_array($item['saran_plat_dasar'])) {
                        foreach ($item['saran_plat_dasar'] as $saranData) {
                            $canvasFile = null;
                            
                            // Handle canvas_image base64 data
                            if (isset($saranData['canvas_image']) && !empty($saranData['canvas_image'])) {
                                try {
                                    // Simpan di dalam root folder canvas_woitem
                                    $folderName = 'canvas_woitem/' . $workOrderPlanningItem->id . '_' . $saranData['item_barang_id'];
                                    
                                    // Generate filename as canvas_image.jpg
                                    $filename = 'canvas_image';
                                    
                                    // Save base64 image using FileHelper
                                    $result = FileHelper::saveBase64AsJpg(
                                        $saranData['canvas_image'], 
                                        $folderName, 
                                        $filename
                                    );
                                    
                                    if ($result['success']) {
                                        $canvasFile = $result['data']['path'];
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to save canvas image: ' . $e->getMessage());
                                    // Continue without canvas file if saving fails
                                }
                            }
                            
                            // Calculate and update sisa_luas if canvas_layout is provided
                            $this->updateSisaLuas($saranData);
                            
                            SaranPlatShaftDasar::create([
                                'wo_planning_item_id' => $workOrderPlanningItem->id,
                                'item_barang_id' => $saranData['item_barang_id'],
                                'quantity' => $saranData['quantity'] ?? null,
                                'canvas_file' => $canvasFile,
                            ]);
                        }
                    }
                }
            }

            // Update sequence counter setelah berhasil create WorkOrderPlanning
            $this->documentSequenceController->increaseSequence('wo');

            DB::commit();

            // Load relasi setelah simpan
            $workOrderPlanning->load(['workOrderPlanningItems.hasManyPelaksana.pelaksana', 'workOrderPlanningItems.hasManySaranPlatShaftDasar.itemBarang', 'salesOrder']);

            return $this->successResponse($workOrderPlanning, 'Work Order Planning berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->errorResponse('Gagal menyimpan Work Order Planning: ' . $e->getMessage(), 500);
        }
    }

    public function show($id, Request $request)
    {
        $data = WorkOrderPlanning::with([
            'workOrderPlanningItems.hasManyPelaksana.pelaksana',
            'workOrderPlanningItems.jenisBarang',
            'workOrderPlanningItems.bentukBarang',
            'workOrderPlanningItems.gradeBarang',
            // Tambahkan relasi saran plat/shaft dasar dan plat dasar
            'workOrderPlanningItems.hasManySaranPlatShaftDasar.itemBarang',
            'workOrderPlanningItems.platDasar',
            // Tambahkan relasi header agar lengkap
            'salesOrder',
            'pelanggan',
            'gudang',
            'pelaksana',
        ])->find($id);
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        // Transform data to return meaningful information
        $transformedData = [
            'id' => $data->id,
            'wo_unique_id' => $data->wo_unique_id,
            'nomor_wo' => $data->nomor_wo,
            'tanggal_wo' => $data->tanggal_wo,
            'prioritas' => $data->prioritas,
            'status' => $data->status,
            'catatan' => $data->catatan,
            'created_at' => $data->created_at,
            'updated_at' => $data->updated_at,
            'close_wo_at' => $data->close_wo_at,
            'has_generated_invoice' => $data->has_generated_invoice,
            'has_generated_pod' => $data->has_generated_pod,
            
            // Sales Order information
            'sales_order' => [
                'id' => $data->salesOrder->id ?? null,
                'nomor_so' => $data->salesOrder->nomor_so ?? null,
                'tanggal_so' => $data->salesOrder->tanggal_so ?? null,
                'tanggal_pengiriman' => $data->salesOrder->tanggal_pengiriman ?? null,
                'syarat_pembayaran' => $data->salesOrder->syarat_pembayaran ?? null,
                'handover_method' => $data->salesOrder->handover_method ?? null,
            ],
            
            // Master data information
            'pelanggan' => [
                'id' => $data->pelanggan->id ?? null,
                'nama_pelanggan' => $data->pelanggan->nama_pelanggan ?? null,
            ],
            'gudang' => [
                'id' => $data->gudang->id ?? null,
                'nama_gudang' => $data->gudang->nama_gudang ?? null,
            ],
            'pelaksana' => [
                'id' => $data->pelaksana->id ?? null,
                'nama_pelaksana' => $data->pelaksana->nama_pelaksana ?? null,
            ],
            
            // Transform items with meaningful data
            'workOrderPlanningItems' => $data->workOrderPlanningItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'wo_item_unique_id' => $item->wo_item_unique_id,
                    'qty' => $item->qty,
                    'panjang' => $item->panjang,
                    'lebar' => $item->lebar,
                    'tebal' => $item->tebal,
                    'berat' => $item->berat,
                    'satuan' => $item->satuan,
                    'diskon' => $item->diskon,
                    'catatan' => $item->catatan,
                    'jenis_potongan' => $item->jenis_potongan,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    
                    // Master data with ID and name
                    'jenis_barang' => [
                        'id' => $item->jenisBarang->id ?? null,
                        'nama_jenis_barang' => $item->jenisBarang->nama_jenis ?? null,
                    ],
                    'bentuk_barang' => [
                        'id' => $item->bentukBarang->id ?? null,
                        'nama_bentuk_barang' => $item->bentukBarang->nama_bentuk ?? null,
                    ],
                    'grade_barang' => [
                        'id' => $item->gradeBarang->id ?? null,
                        'nama_grade_barang' => $item->gradeBarang->nama ?? null,
                    ],
                    'plat_dasar' => [
                        'id' => $item->platDasar->id ?? null,
                        'nama_item_barang' => $item->platDasar->nama_item_barang ?? null,
                    ],
                    
                    // Transform pelaksana with meaningful data
                    'pelaksana' => $item->hasManyPelaksana->map(function ($pelaksana) {
                        return [
                            'id' => $pelaksana->id,
                            'qty' => $pelaksana->qty,
                            'weight' => $pelaksana->weight,
                            'tanggal' => $pelaksana->tanggal,
                            'jam_mulai' => $pelaksana->jam_mulai,
                            'jam_selesai' => $pelaksana->jam_selesai,
                            'catatan' => $pelaksana->catatan,
                            'created_at' => $pelaksana->created_at,
                            'updated_at' => $pelaksana->updated_at,
                            'pelaksana_info' => [
                                'id' => $pelaksana->pelaksana->id ?? null,
                                'nama_pelaksana' => $pelaksana->pelaksana->nama_pelaksana ?? null,
                            ],
                        ];
                    }),
                    
                    // Transform saran plat with meaningful data
                    'saran_plat_dasar' => $item->hasManySaranPlatShaftDasar->map(function ($saran) {
                        return [
                            'id' => $saran->id,
                            'is_selected' => $saran->is_selected,
                            'quantity' => $saran->quantity,
                            'created_at' => $saran->created_at,
                            'updated_at' => $saran->updated_at,
                            'item_barang' => [
                                'id' => $saran->itemBarang->id ?? null,
                                'nama_item_barang' => $saran->itemBarang->nama_item_barang ?? null,
                            ],
                        ];
                    }),
                ];
            }),
        ];
        
        // Cek apakah perlu membuat WorkOrderActual
        $createActual = $request->boolean('create_actual', false);
        if ($createActual) {
            DB::beginTransaction();
            try {
                // Cek apakah sudah ada WorkOrderActual untuk WorkOrderPlanning ini
                $existingActual = WorkOrderActual::where('work_order_planning_id', $id)->first();
                
                if (!$existingActual) {
                    // Buat WorkOrderActual baru
                    $workOrderActual = WorkOrderActual::create([
                        'work_order_planning_id' => $id,
                        'tanggal_actual' => now(),
                        'status' => 'On Progress',
                        'catatan' => 'Dibuat otomatis dari Work Order Planning'
                    ]);
                    
                    // Update status work order planning menjadi 'On Progress'
                    $workOrderPlanningUpdated = WorkOrderPlanning::where('id', $id)->update(['status' => 'On Progress']);
                    Log::info("Work Order Planning update result: " . ($workOrderPlanningUpdated ? 'success' : 'failed'));
                    
                    // Commit transaction
                    DB::commit();
                    
                    // Tambahkan informasi WorkOrderActual ke response
                    $transformedData['work_order_actual'] = $workOrderActual;
                } else {
                    // Jika sudah ada, tambahkan informasi yang sudah ada
                    $transformedData['work_order_actual'] = $existingActual;
                }
            } catch (\Exception $e) {
                // Rollback transaction jika ada error
                DB::rollback();
                Log::error('Gagal membuat WorkOrderActual: ' . $e->getMessage());
                return $this->errorResponse('Gagal membuat Work Order Actual: ' . $e->getMessage(), 500);
            }
        }
        
        return $this->successResponse($transformedData);
    }

    public function showItem($id, Request $request)
    {
        $data = WorkOrderPlanningItem::with(['jenisBarang', 'bentukBarang', 'gradeBarang', 'platDasar', 'hasManySaranPlatShaftDasar.itemBarang'])->find($id);
        
        if (!$data) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }
        
        // Cek apakah perlu membuat WorkOrderActual
        $createActual = $request->boolean('create_actual', false);
        if ($createActual) {
            // Validasi parameter wo_actual_id
            $woActualId = $request->input('wo_actual_id');
            if (!$woActualId) {
                return $this->errorResponse('Parameter wo_actual_id diperlukan', 400);
            }
            
            DB::beginTransaction();
            try {
                // Cek apakah sudah ada WorkOrderActual untuk WorkOrderPlanning ini
                $existingActual = WorkOrderActualItem::where('wo_plan_item_id', $data->id)->first();

                if (!$existingActual) {
                    // Buat WorkOrderActualItem baru dengan wo_actual_id dari parameter
                    $workOrderActualItem = WorkOrderActualItem::create([
                        'wo_plan_item_id' => $data->id,
                        'work_order_actual_id' => $woActualId,
                        'panjang_actual' => $data->panjang,
                        'lebar_actual' => $data->lebar,
                        'tebal_actual' => $data->tebal,
                        'qty_actual' => $data->qty,
                        'berat_actual' => $data->berat,
                        'jenis_barang_id' => $data->jenis_barang_id,
                        'bentuk_barang_id' => $data->bentuk_barang_id,
                        'grade_barang_id' => $data->grade_barang_id,
                        'plat_dasar_id' => $data->plat_dasar_id,
                        'satuan' => $data->satuan,
                        'catatan' => $data->catatan,
                    ]);
                    // Commit transaction
                    DB::commit();
                    
                    // Tambahkan informasi WorkOrderActual ke response
                    $data->work_order_actual_item = $workOrderActualItem;
                } else {
                    // Jika sudah ada, tambahkan informasi yang sudah ada
                    $data->work_order_actual_item = $existingActual;
                }
            } catch (\Exception $e) {
                // Rollback transaction jika ada error
                DB::rollback();
                Log::error('Error creating WorkOrderActual: ' . $e->getMessage());
                return $this->errorResponse('Gagal membuat Work Order Actual: ' . $e->getMessage(), 500);
            }
        }
        
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
            'berat' => 'nullable|numeric|min:0',
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
            'saran_plat_dasar.*.quantity' => 'nullable|numeric|min:0',
            'saran_plat_dasar.*.canvas_image' => 'nullable|string',
            'saran_plat_dasar.*.canvas_layout' => 'nullable|string',
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
                            'wo_plan_item_id' => $data->id,
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
                        $canvasFile = null;
                        
                        // Handle canvas_image base64 data
                        if (isset($saranData['canvas_image']) && !empty($saranData['canvas_image'])) {
                            try {
                                // Simpan di dalam root folder canvas_woitem
                                $folderName = 'canvas_woitem/' . $data->id . '_' . $saranData['item_barang_id'];
                                
                                // Generate filename as canvas_image.jpg
                                $filename = 'canvas_image';
                                
                                // Save base64 image using FileHelper
                                $result = FileHelper::saveBase64AsJpg(
                                    $saranData['canvas_image'], 
                                    $folderName, 
                                    $filename
                                );
                                
                                if ($result['success']) {
                                    $canvasFile = $result['data']['path'];
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to save canvas image: ' . $e->getMessage());
                                // Continue without canvas file if saving fails
                            }
                        }
                        
                        // Calculate and update sisa_luas if canvas_layout is provided
                        $this->updateSisaLuas($saranData);
                        
                        SaranPlatShaftDasar::create([
                            'wo_planning_item_id' => $data->id,
                            'item_barang_id' => $saranData['item_barang_id'],
                            'quantity' => $saranData['quantity'] ?? null,
                            'is_selected' => $saranData['is_selected'] ?? false,
                            'canvas_file' => $canvasFile,
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
                'wo_plan_item_id' => $item->id,
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
            ->where('wo_plan_item_id', $item->id)
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
            ->where('wo_plan_item_id', $item->id)
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

    /**
     * Update status work order planning
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:draft,On Progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderPlanning = WorkOrderPlanning::find($id);
        if (!$workOrderPlanning) {
            return $this->errorResponse('Data tidak ditemukan', 404);
        }

        DB::beginTransaction();
        try {
            // Update status work order planning
            $workOrderPlanning->status = $request->status;
            $workOrderPlanning->save();

            // Update status sales order jika ada
            if ($workOrderPlanning->id_sales_order) {
                SalesOrder::where('id', $workOrderPlanning->id_sales_order)->update(['status' => $request->status]);
            }

            DB::commit();
            return $this->successResponse($workOrderPlanning, 'Status berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Gagal update status: ' . $e->getMessage());
            return $this->errorResponse('Gagal update status: ' . $e->getMessage(), 500);
        }
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
 

    /**
     * Get semua saran plat/shaft dasar untuk item tertentu
     */
    public function getSaranPlatDasarByItem($itemId)
    {
        $item = WorkOrderPlanningItem::find($itemId);
        if (!$item) {
            return $this->errorResponse('Data item tidak ditemukan', 404);
        }

        // Ambil user_id dari JWT token
        $currentUserId = auth()->id();
        
        $saranPlatDasar = SaranPlatShaftDasar::with('itemBarang')
            ->where('wo_planning_item_id', $item->id)
            ->whereHas('itemBarang', function($query) use ($currentUserId) {
                $query->where(function($q) use ($currentUserId) {
                    $q->where('is_edit', false)
                      ->orWhereNull('is_edit')
                      ->orWhere('user_id', $currentUserId); // Kalau yang edit user yang sama, tetap return
                });
            })
            ->orderBy('is_selected', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->successResponse($saranPlatDasar);
    }

    /**
     * Download canvas file untuk saran plat dasar
     */
    public function downloadCanvasFile($saranId)
    {
        $saranPlatDasar = SaranPlatShaftDasar::find($saranId);
        
        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran tidak ditemukan', 404);
        }

        if (!$saranPlatDasar->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan', 404);
        }

        $filePath = storage_path('app/public/' . $saranPlatDasar->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        return response()->download($filePath);
    }

    /**
     * Get canvas file content untuk saran plat dasar
     */
    public function getCanvasFile($saranId)
    {
        $saranPlatDasar = SaranPlatShaftDasar::find($saranId);
        
        if (!$saranPlatDasar) {
            return $this->errorResponse('Data saran tidak ditemukan', 404);
        }

        if (!$saranPlatDasar->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan', 404);
        }

        $filePath = storage_path('app/public/' . $saranPlatDasar->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        $content = file_get_contents($filePath);
        
        return response()->json([
            'success' => true,
            'data' => [
                'canvas_data' => json_decode($content, true),
                'file_path' => $saranPlatDasar->canvas_file
            ]
        ]);
    }

    /**
     * Get canvas file content berdasarkan item barang ID
     */
    public function getCanvasFileByItemBarang($itemBarangId)
    {
        $itemBarang = ItemBarang::find($itemBarangId);
        
        if (!$itemBarang) {
            return $this->errorResponse('Data item barang tidak ditemukan', 404);
        }

        if (!$itemBarang->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan untuk item ini', 404);
        }

        $filePath = storage_path('app/public/' . $itemBarang->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        $content = file_get_contents($filePath);
        
        return response()->json([
            'success' => true,
            'data' => [
                'item_barang_id' => $itemBarangId,
                'canvas_data' => json_decode($content, true),
                'file_path' => $itemBarang->canvas_file
            ]
        ]);
    }

    /**
     * Get canvas file content berdasarkan item barang ID
     */
    public function getCanvasByItemId($itemBarangId)
    {
        $itemBarang = ItemBarang::find($itemBarangId);
        
        if (!$itemBarang) {
            return $this->errorResponse('Data item barang tidak ditemukan', 404);
        }

        if (!$itemBarang->canvas_file) {
            return $this->errorResponse('File canvas tidak ditemukan untuk item ini', 404);
        }

        $filePath = storage_path('app/public/' . $itemBarang->canvas_file);
        
        if (!file_exists($filePath)) {
            return $this->errorResponse('File tidak ditemukan di storage', 404);
        }

        $content = file_get_contents($filePath);
        
        // Return hanya isi JSON canvas saja
        return response()->json(json_decode($content, true));
    }

    /**
     * Get canvas image berdasarkan item barang ID
     */
    public function getCanvasImageByItemId($itemBarangId)
    {
        $itemBarang = ItemBarang::find($itemBarangId);
        
        if (!$itemBarang) {
            return $this->errorResponse('Data item barang tidak ditemukan', 404);
        }

        if (!$itemBarang->canvas_image) {
            return $this->errorResponse('Canvas image tidak ditemukan untuk item ini', 404);
        }

        $imagePath = storage_path('app/public/' . $itemBarang->canvas_image);
        
        if (!file_exists($imagePath)) {
            return $this->errorResponse('File image tidak ditemukan di storage', 404);
        }

        $imageData = file_get_contents($imagePath);
        $base64 = base64_encode($imageData);
        
        return response()->json([
            'canvas_image' => 'data:image/jpeg;base64,' . $base64
        ]);
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

    /**
     * Get semua canvas images dari Work Order berdasarkan WO ID
     * Loop ke dalam child WO items dan ambil path image dari saran plat dasar
     */
    public function getWorkOrderImages($id)
    {
        try {
            // Cari Work Order dengan relasi ke items dan saran plat dasar
            $workOrder = WorkOrderPlanning::with([
                'workOrderPlanningItems.hasManySaranPlatShaftDasar.itemBarang'
            ])->find($id);

            if (!$workOrder) {
                return $this->errorResponse('Work Order tidak ditemukan', 404);
            }

            $images = [];

            // Loop melalui setiap item dalam work order
            foreach ($workOrder->workOrderPlanningItems as $item) {
                // Loop melalui setiap saran plat dasar dalam item
                foreach ($item->hasManySaranPlatShaftDasar as $saran) {
                    // Jika ada canvas_file (path image), tambahkan ke array
                    if (!empty($saran->canvas_file)) {
                        $base64Image = null;
                        $filePath = storage_path('app/public/' . $saran->canvas_file);
                        
                        // Cek apakah file exists dan convert ke base64
                        if (file_exists($filePath)) {
                            $imageData = file_get_contents($filePath);
                            $base64Image = 'data:image/jpeg;base64,' . base64_encode($imageData);
                        }

                        $images[] = [
                            'wo_id' => $workOrder->id,
                            'wo_unique_id' => $workOrder->wo_unique_id,
                            'wo_item_id' => $item->id,
                            'wo_item_unique_id' => $item->wo_item_unique_id,
                            'saran_id' => $saran->id,
                            'item_barang_id' => $saran->item_barang_id,
                            'item_barang_name' => $saran->itemBarang->nama_item_barang ?? null,
                            'canvas_file_path' => $saran->canvas_file,
                            'canvas_image_base64' => $base64Image,
                            'is_selected' => $saran->is_selected ?? false,
                            'quantity' => $saran->quantity,
                            'created_at' => $saran->created_at,
                            'updated_at' => $saran->updated_at,
                        ];
                    }
                }
            }

            // Response dengan informasi work order dan semua images
            $response = [
                'work_order' => [
                    'id' => $workOrder->id,
                    'wo_unique_id' => $workOrder->wo_unique_id,
                    'nomor_wo' => $workOrder->nomor_wo,
                    'tanggal_wo' => $workOrder->tanggal_wo,
                    'status' => $workOrder->status,
                    'prioritas' => $workOrder->prioritas,
                ],
                'total_images' => count($images),
                'images' => $images
            ];

            return $this->successResponse($response, 'Canvas images berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil canvas images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update sisa luas item barang based on canvas layout
     */
    private function updateSisaLuas($saranData)
    {
        try {
            // Check if canvas_layout is provided and item_barang_id exists
            if (!isset($saranData['canvas_layout']) || empty($saranData['canvas_layout']) || !isset($saranData['item_barang_id'])) {
                return;
            }

            // Parse canvas layout JSON
            $canvasLayout = json_decode($saranData['canvas_layout'], true);
            
            if (!$canvasLayout || !isset($canvasLayout['metadata'])) {
                return;
            }

            $metadata = $canvasLayout['metadata'];
            
            // Get containerArea and totalArea from metadata
            $containerArea = $metadata['containerArea'] ?? 0;
            $totalArea = $metadata['totalArea'] ?? 0;
            
            // Calculate sisa luas = containerArea - totalArea
            $sisaLuas = $containerArea - $totalArea;
            
            // Update item barang sisa_luas
            if ($sisaLuas >= 0) {
                \App\Models\MasterData\ItemBarang::where('id', $saranData['item_barang_id'])
                    ->update(['sisa_luas' => $sisaLuas]);
                    
                Log::info("Updated sisa_luas for item_barang_id {$saranData['item_barang_id']}: {$sisaLuas}");
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to update sisa_luas: ' . $e->getMessage());
        }
    }

}
