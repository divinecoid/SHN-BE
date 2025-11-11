<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Transactions\WorkOrderPlanning;

class WorkOrderPlanningDashboardController extends Controller
{
    public function index()
    {
        $now = now();

        return WorkOrderPlanning::selectRaw('YEAR(created_at) as year, DATE_FORMAT(created_at, "%M") as month, DATE_FORMAT(created_at, "%d") as day, COUNT(*) as total')
            ->whereYear('created_at', $now->year)
            ->whereMonth('created_at', $now->month)
            ->groupByRaw('YEAR(created_at), DATE_FORMAT(created_at, "%M"), DATE_FORMAT(created_at, "%d")')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }
}