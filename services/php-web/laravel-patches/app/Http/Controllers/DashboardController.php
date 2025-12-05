<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\DashboardService;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index()
    {
        $data = $this->dashboardService->getDashboardData();

        return view('dashboard', $data);
    }

    public function jwstFeed(Request $r)
    {
        $data = $this->dashboardService->getJwstFeed($r);

        return response()->json($data);
    }

    public function downloadCsv($filename)
    {
        $path = $this->dashboardService->downloadCsv($filename);

        return response()->download($path, $filename);
    }
}
