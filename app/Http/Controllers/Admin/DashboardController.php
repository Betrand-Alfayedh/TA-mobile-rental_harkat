<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Supir;

class DashboardController extends Controller
{
    public function index()
    {
        $totalMobil = Mobil::count();
        $totalSupir = Supir::count();
        $mobilTersedia = Mobil::where('status', 1)->where('status_approval', 1) ->count();
        $supirBertugas = Supir::where('status', 2)->count();
        $mobilDisewa = Mobil::where('status', Mobil::STATUS_DISEWA)->count();
        $supirSiap = Supir::where('status', 1)->count();

        $menungguVerifikasi = Pembayaran::where('status_pembayaran', 2)->count();
        // Unit bersopir dari pesanan Booked yang belum punya sopir dan belum ditawarkan ke siapa pun.
        $perluPenugasan = BookingDetail::where('status', Booking::STATUS_BOOKED)
            ->whereHas('booking', fn ($b) => $b->where('status', Booking::STATUS_BOOKED))
            ->where('pakai_supir', '>', 0)->whereNull('supir_id')
            ->whereDoesntHave('jobOffers', fn ($o) => $o->where('status', 0))->count();
        $pesananBerjalan = Booking::where('status', Booking::STATUS_ONGOING)->count();
        $terbaru = Booking::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', compact(
            'totalMobil', 'mobilTersedia', 'mobilDisewa', 'totalSupir', 'supirBertugas', 'supirSiap',
            'menungguVerifikasi', 'perluPenugasan', 'pesananBerjalan', 'terbaru'
        ));
    }
}
