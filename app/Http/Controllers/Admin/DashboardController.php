<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mobil;
use App\Models\Supir;

class DashboardController extends Controller
{
    public function index()
    {
        $totalMobil = Mobil::count();
        $totalSupir = Supir::count();
        $mobilTersedia = Mobil::where('status', 1)->where('status_approval', 1) ->count();
        $supirBertugas = Supir::where('status', 2)->count();
        $mobilDisewa = Mobil::where('status', 2)->count();
        $supirSiap = Supir::where('status', 1)->count();

        return view('admin.dashboard', compact('totalMobil', 'mobilTersedia', 'mobilDisewa', 'totalSupir', 'supirBertugas', 'supirSiap'));
    }
}
