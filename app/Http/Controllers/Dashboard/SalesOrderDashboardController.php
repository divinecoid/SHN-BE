<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MasterData\SalesOrder;

class SalesOrderDashboardController extends Controller
{
    public function index()
    {
        $now = now();

        return SalesOrder::selectRaw('YEAR(tanggal_so) as year, DATE_FORMAT(tanggal_so, "%M") as month, DATE_FORMAT(tanggal_so, "%d") as day, COUNT(*) as total')
            ->whereYear('tanggal_so', $now->year)
            ->whereMonth('tanggal_so', $now->month)
            ->groupByRaw('YEAR(tanggal_so), DATE_FORMAT(tanggal_so, "%M"), DATE_FORMAT(tanggal_so, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }
}