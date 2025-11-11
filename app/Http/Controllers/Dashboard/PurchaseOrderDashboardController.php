<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Transactions\PurchaseOrder;

class PurchaseOrderDashboardController extends Controller
{
    public function index()
    {
        $now = now();

        return PurchaseOrder::selectRaw('YEAR(tanggal_po) as year, DATE_FORMAT(tanggal_po, "%M") as month, DATE_FORMAT(tanggal_po, "%d") as day, COUNT(*) as total')
            ->whereYear('tanggal_po', $now->year)
            ->whereMonth('tanggal_po', $now->month)
            ->groupByRaw('YEAR(tanggal_po), DATE_FORMAT(tanggal_po, "%M"), DATE_FORMAT(tanggal_po, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }
}