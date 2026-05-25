<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index()
    {
        // ambil data booking per bulan tahun ini
        $bookings = Booking::selectRaw('MONTH(tanggal_booking) as month, COUNT(*) as total')
            ->whereYear('tanggal_booking', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $chartLabels = [];
        $chartData = [];

        for ($i = 1; $i <= 12; $i++) {
            $chartLabels[] = Carbon::create()->month($i)->format('F');
            $monthData = $bookings->firstWhere('month', $i);
            $chartData[] = $monthData ? $monthData->total : 0;
        }
        return view('superadmin.dashboard', compact('chartLabels', 'chartData'));
    }

    public function listLaporan()
    {
        $folderPath = storage_path('app/public/laporan');
        $files = collect();

        if (File::exists($folderPath)) {
            $files = collect(File::files($folderPath));
        }

        return view('superadmin.laporan.index', compact('files'));
    }
}
