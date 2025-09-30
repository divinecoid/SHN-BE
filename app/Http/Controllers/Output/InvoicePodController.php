<?php

namespace App\Http\Controllers\Output;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Output\InvoicePod;
use App\Models\Output\InvoicePodItem;
use App\Models\MasterData\SalesOrder;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderActualItem;
use App\Models\Transactions\WorkOrderActual;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InvoicePodController extends Controller
{
    use ApiFilterTrait;

    public function generateInvoicePod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'work_order_planning_id' => 'required|exists:trx_work_order_planning,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        DB::beginTransaction();
        try {
            $workOrderPlanning = WorkOrderPlanning::find($request->work_order_planning_id);
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

            $now = microtime(true);
            // Use UTC+7 (Asia/Jakarta) for date/time generation
            $dt = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
            $datePart = $dt->format('Ymd');
            $timePart = $dt->format('His');
            $milliseconds = sprintf('%03d', ($now - floor($now)) * 1000);
            $nomorInvoice = 'INV-' . $datePart . '-' . $timePart . $milliseconds;
            $nomorPod = 'POD-' . $datePart . '-' . $timePart . $milliseconds;
            $invoicePod = InvoicePod::create([
                'work_order_planning_id' => $workOrderPlanning->id,
                'work_order_actual_id' => $workOrderActual->id,
                'sales_order_id' => $salesOrder->id,
                'nomor_invoice' => $nomorInvoice,
                'nomor_pod' => $nomorPod,
                'total_harga_invoice' => $workOrderPlanning->total_harga,
                'discount_invoice' => $workOrderPlanning->diskon,
                'biaya_lain' => $workOrderPlanning->biaya_lain,
                'ppn_invoice' => $workOrderPlanning->ppn,
                'grand_total' => $workOrderPlanning->grand_total,
                'uang_muka' => $workOrderPlanning->uang_muka,
                'sisa_bayar' => $workOrderPlanning->sisa_bayar,
                'handover_method' => $workOrderPlanning->handover_method,
            ]);
            foreach ($workOrderActual->workOrderActualItems as $item) {
                $namaItem = $item->workOrderPlanningItem->bentukBarang->nama_bentuk . ' ' . $item->workOrderPlanningItem->jenisBarang->nama_jenis . ' ' . $item->workOrderPlanningItem->gradeBarang->nama;
                if (is_null($item->lebar_actual)) {
                    $dimensiPotong = $item->panjang_actual . ' x ' . $item->tebal_actual;
                } else {
                    $dimensiPotong = $item->panjang_actual . ' x ' . $item->lebar_actual . ' x ' . $item->tebal_actual;
                }
                $invoicePodItem = InvoicePodItem::create([
                    'invoicepod_id' => $invoicePod->id,
                    'nama_item' => $namaItem,
                    'unit' => ($item->satuan === 'PCS') ? 'utuh' : 'potongan',
                    'dimensi_potong' => $dimensiPotong,
                    'qty' => $item->qty_actual,
                    'total_kg' => $item->berat_actual,
                    'harga_per_unit' => $item->workOrderPlanningItem->harga,
                    'total_harga' => ($item->satuan === 'PCS')
                        ? ($item->workOrderPlanningItem->harga * $item->qty_actual)
                        : ($item->workOrderPlanningItem->harga * $item->berat_actual),
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
        DB::commit();
        return $this->successResponse('Invoice POD created successfully', $invoicePod);
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
