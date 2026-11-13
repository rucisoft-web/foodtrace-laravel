<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Support\Wms;

class DashboardController extends Controller
{
    public function index()
    {
        $zone = request('zone');
        $zone = isset(Wms::ZONES[$zone]) ? $zone : null;

        return view('dashboard', ['d' => DashboardService::summary($zone), 'zone' => $zone]);
    }
}
