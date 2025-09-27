<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiFilterTrait;
use App\Models\Transactions\WorkOrderActual;
use App\Models\Transactions\WorkOrderActualItem;
use App\Models\Transactions\WorkOrderActualPelaksana;
use App\Models\Transactions\WorkOrderPlanning;
use App\Models\Transactions\WorkOrderPlanningItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WorkOrderActualController extends Controller
{
    use ApiFilterTrait;

    
    public function saveWorkOrderActual(Request $request)
    {
        // Validasi request tidak boleh kosong
        if (empty($request->all()) || count($request->all()) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak boleh kosong'
            ], 400);
        }
        try {
            // Validasi input untuk struktur baru
            $validator = Validator::make($request->all(), [
                'actualWorkOrderId' => 'required|integer',
                'planningWorkOrderId' => 'required|integer',
                'items' => 'required|array',
                'items.*.qtyActual' => 'required|numeric|min:0',
                'items.*.beratActual' => 'required|numeric|min:0',
                'items.*.assignments' => 'required|array',
                'items.*.assignments.*.id' => 'required|integer',
                'items.*.assignments.*.qty' => 'required|integer|min:1',
                'items.*.assignments.*.berat' => 'required|numeric|min:0',
                'items.*.assignments.*.pelaksana' => 'required|string',
                'items.*.assignments.*.pelaksana_id' => 'required|integer',
                'items.*.assignments.*.tanggal' => 'required|date',
                'items.*.assignments.*.jamMulai' => 'required|date_format:H:i:s',
                'items.*.assignments.*.jamSelesai' => 'required|date_format:H:i:s',
                'items.*.assignments.*.catatan' => 'nullable|string',
                'items.*.assignments.*.status' => 'required|string',
                'items.*.timestamp' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $results = [];
            $actualWorkOrderId = $request->input('actualWorkOrderId');
            $planningWorkOrderId = $request->input('planningWorkOrderId');
            $items = $request->input('items', []);

            foreach ($items as $planItemId => $data) {
                // Cek apakah WorkOrderPlanningItem ada
                $planningItem = WorkOrderPlanningItem::find($planItemId);
                if (!$planningItem) {
                    throw new \Exception("WorkOrderPlanningItem dengan ID {$planItemId} tidak ditemukan");
                }

                // Cek apakah sudah ada WorkOrderActualItem untuk planning item ini
                $existingActualItem = WorkOrderActualItem::where('wo_plan_item_id', $planItemId)->first();
                
                if ($existingActualItem) {
                    // Hapus semua data pelaksana yang terkait
                    WorkOrderActualPelaksana::where('wo_actual_item_id', $existingActualItem->id)->delete();
                    
                    // Hapus WorkOrderActualItem yang sudah ada
                    $existingActualItem->delete();
                }

                // Gunakan actualWorkOrderId dari request
                $workOrderActualId = $actualWorkOrderId;

                // Validasi jika work_order_actual_id null
                if (is_null($workOrderActualId)) {
                    throw new \Exception("actualWorkOrderId tidak boleh null");
                }

                // Buat WorkOrderActualItem baru
                $actualItem = WorkOrderActualItem::create([
                    'work_order_actual_id' => $workOrderActualId,
                    'wo_plan_item_id' => $planItemId,
                    'qty_actual' => $data['qtyActual'],
                    'berat_actual' => $data['beratActual'],
                    'jenis_barang_id' => $planningItem->jenis_barang_id,
                    'bentuk_barang_id' => $planningItem->bentuk_barang_id,
                    'grade_barang_id' => $planningItem->grade_barang_id,
                    'plat_dasar_id' => $planningItem->plat_dasar_id,
                    'panjang_actual' => $planningItem->panjang,
                    'lebar_actual' => $planningItem->lebar,
                    'tebal_actual' => $planningItem->tebal,
                    'catatan' => $planningItem->catatan,
                ]);

                // Buat data pelaksana (assignments)
                $pelaksanaData = [];
                foreach ($data['assignments'] as $assignment) {
                    $pelaksanaData[] = [
                        'wo_actual_item_id' => $actualItem->id,
                        'pelaksana_id' => $assignment['pelaksana_id'],
                        'qty' => $assignment['qty'],
                        'weight' => $assignment['berat'],
                        'tanggal' => $assignment['tanggal'],
                        'jam_mulai' => $assignment['jamMulai'],
                        'jam_selesai' => $assignment['jamSelesai'],
                        'catatan' => $assignment['catatan'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Insert semua data pelaksana sekaligus
                WorkOrderActualPelaksana::insert($pelaksanaData);

                $results[] = [
                    'plan_item_id' => $planItemId,
                    'actual_item_id' => $actualItem->id,
                    'qty_actual' => $data['qtyActual'],
                    'berat_actual' => $data['beratActual'],
                    'assignments_count' => count($data['assignments']),
                    'timestamp' => $data['timestamp']
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data WorkOrderActual berhasil disimpan',
                'data' => [
                    'actualWorkOrderId' => $actualWorkOrderId,
                    'planningWorkOrderId' => $planningWorkOrderId,
                    'items' => $results
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
