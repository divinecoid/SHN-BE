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
            'work_order_planning_id' => 'required|exists:trx_work_order_planning,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderPlanning = WorkOrderPlanning::find($request->work_order_planning_id);
        if (!$workOrderPlanning) {
            return $this->errorResponse('Work order tidak ditemukan', 404);
        }
        $invoicePod = $workOrderPlanning->invoicePod;
        if (!$invoicePod) {
            return $this->errorResponse('Invoice belum digenerate', 400);
        }

        $workOrderPlanning->has_generated_invoice = true;
        $workOrderPlanning->save();
        if (is_null($invoicePod->tanggal_cetak_invoice)) {
            $invoicePod->update(['tanggal_cetak_invoice' => now()->setTimezone('Asia/Jakarta')]);
        }

        return $this->successResponse('Invoice found', $invoicePod);
    }

    public function viewPod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'work_order_planning_id' => 'required|exists:trx_work_order_planning,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $workOrderPlanning = WorkOrderPlanning::find($request->work_order_planning_id);
        if (!$workOrderPlanning) {
            return $this->errorResponse('Work order tidak ditemukan', 404);
        }
        $invoicePod = $workOrderPlanning->invoicePod;
        if (!$invoicePod) {
            return $this->errorResponse('Pod tidak ditemukan', 404);
        }

        $workOrderPlanning->has_generated_pod = true;
        $workOrderPlanning->save();
        if (is_null($invoicePod->tanggal_cetak_pod)) {
            $invoicePod->update(['tanggal_cetak_pod' => now()->setTimezone('Asia/Jakarta')]);
        }

        return $this->successResponse('Pod found', $invoicePod);
    }

}
