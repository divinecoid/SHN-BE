<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MasterData\SalesOrder;
use App\Models\Transactions\PurchaseOrder;
use App\Models\Transactions\WorkOrderActual;
use App\Models\Transactions\WorkOrderPlanning;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get date range from request or default to current month
     */
    private function getDateRange(Request $request)
    {
        $now = now();
        
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        // If both are not provided, default to current month
        if (!$dateFrom && !$dateTo) {
            $dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
            $dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
        } else {
            // If only date_from is provided, set date_to to today
            if ($dateFrom && !$dateTo) {
                $dateTo = $now->format('Y-m-d');
            }
            // If only date_to is provided, set date_from to start of month of date_to
            if ($dateTo && !$dateFrom) {
                $dateFrom = Carbon::parse($dateTo)->startOfMonth()->format('Y-m-d');
            }
        }
        
        return [
            'date_from' => Carbon::parse($dateFrom)->startOfDay(),
            'date_to' => Carbon::parse($dateTo)->endOfDay(),
        ];
    }

    public function purchaseOrder(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        return PurchaseOrder::selectRaw('YEAR(tanggal_po) as year, DATE_FORMAT(tanggal_po, "%M") as month, DATE_FORMAT(tanggal_po, "%d") as day, COUNT(*) as total')
            ->whereBetween('tanggal_po', [$dateRange['date_from'], $dateRange['date_to']])
            ->groupByRaw('YEAR(tanggal_po), DATE_FORMAT(tanggal_po, "%M"), DATE_FORMAT(tanggal_po, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    public function salesOrder(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        return SalesOrder::selectRaw('YEAR(tanggal_so) as year, DATE_FORMAT(tanggal_so, "%M") as month, DATE_FORMAT(tanggal_so, "%d") as day, COUNT(*) as total')
            ->whereBetween('tanggal_so', [$dateRange['date_from'], $dateRange['date_to']])
            ->groupByRaw('YEAR(tanggal_so), DATE_FORMAT(tanggal_so, "%M"), DATE_FORMAT(tanggal_so, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    public function workOrderPlanning(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        return WorkOrderPlanning::selectRaw('YEAR(created_at) as year, DATE_FORMAT(created_at, "%M") as month, DATE_FORMAT(created_at, "%d") as day, COUNT(*) as total')
            ->whereBetween('created_at', [$dateRange['date_from'], $dateRange['date_to']])
            ->groupByRaw('YEAR(created_at), DATE_FORMAT(created_at, "%M"), DATE_FORMAT(created_at, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    public function workOrderActual(Request $request)
    {
        $dateRange = $this->getDateRange($request);

        return WorkOrderActual::selectRaw('YEAR(created_at) as year, DATE_FORMAT(created_at, "%M") as month, DATE_FORMAT(created_at, "%d") as day, COUNT(*) as total')
            ->whereBetween('created_at', [$dateRange['date_from'], $dateRange['date_to']])
            ->groupByRaw('YEAR(created_at), DATE_FORMAT(created_at, "%M"), DATE_FORMAT(created_at, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    public function workshop(Request $request)
    {
        $status = $request->query('status', 'all');
        $dateRange = $this->getDateRange($request);
        $query = WorkOrderPlanning::query();

        // Apply date filter
        $query->whereBetween('created_at', [$dateRange['date_from'], $dateRange['date_to']]);

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
            $invoicePod = $item->invoicePod()->first(['nomor_invoice', 'tanggal_cetak_invoice']);
            $pelanggan = $item->pelanggan()->first(['nama_pelanggan']);
            return [
                'nama_customer' => $pelanggan ? $pelanggan->nama_pelanggan : null,
                'nomor_so' => $salesOrder ? $salesOrder->nomor_so : null,
                'waktu_so' => $salesOrder ? $salesOrder->created_at : null,
                'nomor_wo' => $item->nomor_wo,
                'waktu_wo' => $item->created_at,
                'estimate_selesai' => $item->estimate_done ?? null,
                'real_selesai' => $item->real_selesai ?? null,
                'close_wo_at' => $item->close_wo_at ? \Carbon\Carbon::parse($item->close_wo_at)->timezone('Asia/Jakarta')->toDateTimeString() : null,
                'nomor_inv' => $invoicePod ? $invoicePod->nomor_invoice : null,
                'waktu_inv' => $invoicePod ? $invoicePod->tanggal_cetak_invoice : null,
            ];
        })->sortByDesc('waktu_so')->values();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard Workshop',
            'work_orders' => $customResponse,
        ]);
    }
}

