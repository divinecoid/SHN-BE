<?php

namespace App\Http\Controllers\Output;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Output\InvoicePod;
use App\Models\Output\InvoicePodItem;
use App\Models\MasterData\SalesOrder;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use App\Models\MasterData\SalesOrderItem;
use App\Models\Transactions\WorkOrderActualItem;
use App\Models\Transactions\WorkOrderActual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\MasterData\DocumentSequenceController;

class InvoicePodController extends Controller
{
    use ApiFilterTrait;
    
    protected $documentSequenceController;
    
    public function __construct()
    {
        $this->documentSequenceController = new DocumentSequenceController();
    }

    public function generateInvoicePod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_wo' => 'required|exists:trx_work_order_planning,nomor_wo'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $workOrderPlanning = WorkOrderPlanning::where('nomor_wo', $request->nomor_wo)->first();
            if (!$workOrderPlanning) {
                return $this->errorResponse('Work order tidak ditemukan', 404);
            }
            if ($workOrderPlanning->invoicePod) {
                return $this->errorResponse('Work order sudah memiliki invoice dan atau surat jalan', 400);
            }

            $salesOrder = SalesOrder::find($workOrderPlanning->id_sales_order);
            if (!$salesOrder) {
                return $this->errorResponse('Sales order tidak ditemukan', 404);
            }
            $workOrderActual = WorkOrderActual::where('work_order_planning_id', $workOrderPlanning->id)->first();
            if (!$workOrderActual) {
                return $this->errorResponse('Work order actual tidak ditemukan', 404);
            }

            // Generate nomor_invoice menggunakan DocumentSequenceController
            $nomorInvoiceResponse = $this->documentSequenceController->generateDocumentSequence('invoice');
            if ($nomorInvoiceResponse->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor Invoice', 500);
            }
            $nomorInvoice = $nomorInvoiceResponse->getData()->data;
            
            // Generate nomor_pod menggunakan DocumentSequenceController
            $nomorPodResponse = $this->documentSequenceController->generateDocumentSequence('pod');
            if ($nomorPodResponse->getStatusCode() !== 200) {
                return $this->errorResponse('Gagal generate nomor POD', 500);
            }
            $nomorPod = $nomorPodResponse->getData()->data;
            $invoicePod = InvoicePod::create([
                'work_order_planning_id' => $workOrderPlanning->id,
                'work_order_actual_id' => $workOrderActual->id,
                'sales_order_id' => $salesOrder->id,
                'nomor_invoice' => $nomorInvoice,
                'nomor_pod' => $nomorPod,
                'total_harga_invoice' => 0, //will be calculated later
                'discount_invoice' => $salesOrder->total_diskon,
                'biaya_lain' => 0, //$salesOrder->biaya_lain,
                'ppn_invoice' => 0, //will be calculated later
                'grand_total' => 0, //will be calculated later
                'uang_muka' => 0, //$workOrderPlanning->uang_muka,
                'sisa_bayar' => 0, // will be calculated later (grand_total - uang_muka)
                'handover_method' => $workOrderPlanning->handover_method,
            ]);
            foreach ($workOrderActual->workOrderActualItems as $item) {
                $workOrderPlanningItem = WorkOrderPlanningItem::find($item->wo_plan_item_id);
                if (is_null($workOrderPlanningItem)) {
                    continue;
                }
                $salesOrderItem = SalesOrderItem::find($workOrderPlanningItem->sales_order_item_id);
                if (is_null($salesOrderItem)) {
                    continue;
                }
                $namaItem = $workOrderPlanningItem->bentukBarang->nama_bentuk . ' ' . $workOrderPlanningItem->jenisBarang->nama_jenis . ' ' . $workOrderPlanningItem->gradeBarang->nama;
                if (is_null($item->lebar_actual)) {
                    $dimensiPotong = intval($item->panjang_actual) . ' x ' . intval($item->tebal_actual);
                } else {
                    $dimensiPotong = intval($item->panjang_actual) . ' x ' . intval($item->lebar_actual) . ' x ' . intval($item->tebal_actual);
                }
                $invoicePodItem = InvoicePodItem::create([
                    'invoicepod_id' => $invoicePod->id,
                    'nama_item' => $namaItem,
                    'unit' => ($item->satuan === 'PCS') ? 'utuh' : 'potongan',
                    'dimensi_potong' => $dimensiPotong,
                    'qty' => $item->qty_actual,
                    'total_kg' => $item->berat_actual,
                    'harga_per_unit' => $salesOrderItem->harga,
                    'total_harga' => ($item->satuan === 'PCS')
                        ? ($salesOrderItem->harga * $item->qty_actual)
                        : ($salesOrderItem->harga * $item->berat_actual),
                ]);
                $invoicePod->total_harga_invoice += $invoicePodItem->total_harga;
            }
            $invoicePod->ppn_invoice = $invoicePod->total_harga_invoice * $salesOrder->ppn_percent / 100;
            $invoicePod->grand_total = $invoicePod->total_harga_invoice + $invoicePod->ppn_invoice;
            $invoicePod->uang_muka = is_null($salesOrder->uang_muka) ? 0 : $salesOrder->uang_muka;
            $invoicePod->sisa_bayar = $invoicePod->grand_total - $invoicePod->uang_muka;
            $invoicePod->save();
            
            // Update sequence counter setelah berhasil create InvoicePod
            $this->documentSequenceController->increaseSequence('invoice');
            $this->documentSequenceController->increaseSequence('pod');
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
        return $this->successResponse([
            'nomor_invoice' => $invoicePod->nomor_invoice,
            'nomor_pod' => $invoicePod->nomor_pod
        ], 'Invoice POD created successfully');
    }

    public function viewInvoice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_wo' => 'required|exists:trx_work_order_planning,nomor_wo'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderPlanning = WorkOrderPlanning::with(['salesOrder.pelanggan'])->where('nomor_wo', $request->nomor_wo)->first();
        if (!$workOrderPlanning) {
            return $this->errorResponse('Work order tidak ditemukan', 404);
        }
        $invoicePod = $workOrderPlanning->invoicePod()->with('invoicePodItems')->first();
        if (!$invoicePod) {
            return $this->errorResponse('Invoice belum digenerate', 400);
        }

        $workOrderPlanning->has_generated_invoice = true;
        $workOrderPlanning->save();
        if (is_null($invoicePod->tanggal_cetak_invoice)) {
            $invoicePod->update(['tanggal_cetak_invoice' => now()->setTimezone('Asia/Jakarta')]);
        }

        // Format response with only the requested attributes
        $responseData = [
            'nomor_so' => $workOrderPlanning->salesOrder->nomor_so ?? null,
            'nomor_wo' => $workOrderPlanning->nomor_wo,
            'nomor_invoice' => $invoicePod->nomor_invoice,
            'nama_customer' => $workOrderPlanning->salesOrder->pelanggan->nama_pelanggan ?? null,
            'tanggal_cetak_invoice' => $invoicePod->tanggal_cetak_invoice,
            'handover_method' => $invoicePod->handover_method,
            'total_harga_invoice' => $invoicePod->total_harga_invoice,
            'discount_invoice' => $invoicePod->discount_invoice,
            'biaya_lain' => $invoicePod->biaya_lain,
            'ppn_invoice' => $invoicePod->ppn_invoice,
            'grand_total' => $invoicePod->grand_total,
            'uang_muka' => $invoicePod->uang_muka,
            'sisa_bayar' => $invoicePod->sisa_bayar,
            'invoice_pod_items' => $invoicePod->invoicePodItems
        ];

        return $this->successResponse($responseData, 'Invoice found');
    }

    public function viewPod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomor_wo' => 'required|exists:trx_work_order_planning,nomor_wo'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderPlanning = WorkOrderPlanning::with(['salesOrder.pelanggan'])->where('nomor_wo', $request->nomor_wo)->first();
        if (!$workOrderPlanning) {
            return $this->errorResponse('Work order tidak ditemukan', 404);
        }
        $podData = $workOrderPlanning->invoicePod()->with('invoicePodItems')->first();
        if (!$podData) {
            return $this->errorResponse('Pod tidak ditemukan', 404);
        }

        $workOrderPlanning->has_generated_pod = true;
        $workOrderPlanning->save();
        if (is_null($podData->tanggal_cetak_pod)) {
            $podData->update(['tanggal_cetak_pod' => now()->setTimezone('Asia/Jakarta')]);
        }

        // Format response with only the requested attributes
        $responseData = [
            'nomor_wo' => $workOrderPlanning->nomor_wo,
            'nomor_so' => $workOrderPlanning->salesOrder->nomor_so ?? null,
            'nama_customer' => $workOrderPlanning->salesOrder->pelanggan->nama_pelanggan ?? null,
            'nomor_pod' => $podData->nomor_pod,
            'tanggal_cetak_pod' => $podData->tanggal_cetak_pod,
            'handover_method' => $podData->handover_method,
            'invoice_pod_items' => $podData->invoicePodItems
        ];

        return $this->successResponse($responseData, 'Pod found');
    }

    public function eligibleForInvoicePod(Request $request)
    {
        $isGenerated = $request->query('is_generated');
        $isPrintedInvoice = $request->query('is_printed_invoice');
        $isPrintedPod = $request->query('is_printed_pod');

        $query = WorkOrderPlanning::where('status', 'selesai')
            ->with(['salesOrder.pelanggan', 'invoicePod']);

        // Filter by is_generated (invoicePod relation existence)
        if ($isGenerated === 'true' || $isGenerated === true || $isGenerated === 1 || $isGenerated === '1') {
            $query->whereHas('invoicePod');
        } elseif ($isGenerated === 'false' || $isGenerated === false || $isGenerated === 0 || $isGenerated === '0') {
            $query->whereDoesntHave('invoicePod');
        }

        // Filter by has_generated_invoice
        if ($isPrintedInvoice === 'true' || $isPrintedInvoice === true || $isPrintedInvoice === 1 || $isPrintedInvoice === '1') {
            $query->where('has_generated_invoice', true);
        } elseif ($isPrintedInvoice === 'false' || $isPrintedInvoice === false || $isPrintedInvoice === 0 || $isPrintedInvoice === '0') {
            $query->where(function($q) {
                $q->whereNull('has_generated_invoice')->orWhere('has_generated_invoice', false);
            });
        }

        // Filter by has_generated_pod
        if ($isPrintedPod === 'true' || $isPrintedPod === true || $isPrintedPod === 1 || $isPrintedPod === '1') {
            $query->where('has_generated_pod', true);
        } elseif ($isPrintedPod === 'false' || $isPrintedPod === false || $isPrintedPod === 0 || $isPrintedPod === '0') {
            $query->where(function($q) {
                $q->whereNull('has_generated_pod')->orWhere('has_generated_pod', false);
            });
        }

        $workOrderPlanning = $query->get();

        $formattedData = $workOrderPlanning->map(function ($item) {
            return [
                'nomor_so' => $item->salesOrder->nomor_so ?? null,
                'nomor_wo' => $item->nomor_wo,
                'nama_customer' => $item->salesOrder->pelanggan->nama_pelanggan ?? null,
                'is_generated' => !is_null($item->invoicePod),
                'has_generated_invoice' => $item->has_generated_invoice ?? false,
                'has_generated_pod' => $item->has_generated_pod ?? false,
            ];
        });

        return $this->successResponse('Work order order selesai', $formattedData);
    }
}
