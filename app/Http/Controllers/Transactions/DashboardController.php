<?php

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Models\Transactions\WorkOrderPlanning;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function workshop(Request $request)
    {
        $status = $request->query('status', 'all');
        $query = WorkOrderPlanning::query();

        if ($status !== 'all') {
            if (in_array(strtolower($status), ['pending', 'onprogress', 'on progress', 'selesai'])) {
                if (strtolower($status) === 'on progress') {
                    $query->where('status', 'on progress');
                } elseif (strtolower($status) === 'onprogress') {
                    $query->where('status', 'on progress');
                } else {
                    $query->where('status', strtolower($status));
                }
            }
        }

        $workOrderPlanning = $query->get();

        // Create custom response with only required attributes
        $customResponse = $workOrderPlanning->map(function ($item) {
            $salesOrder = $item->salesOrder()->first(['nomor_so', 'created_at']);
            
            return [
                'nomor_so' => $salesOrder ? $salesOrder->nomor_so : null,
                'waktu_so' => $salesOrder ? $salesOrder->created_at : null,
                'nomor_wo' => $item->nomor_wo,
                'waktu_wo' => $item->created_at,
                'estimate_selesai' => $item->estimate_selesai ?? null,
                'real_selesai' => $item->real_selesai ?? null,
                'close_wo_at' => $item->close_wo_at ? \Carbon\Carbon::parse($item->close_wo_at)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                'nomor_inv' => null, // Set to null as requested
            ];
        })->sortByDesc('waktu_so')->values();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard Workshop',
            'work_orders' => $customResponse,
        ]);
    }
}
