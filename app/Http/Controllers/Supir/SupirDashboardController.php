<?php

namespace App\Http\Controllers\Supir;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Services\PenugasanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupirDashboardController extends Controller
{
    public function index()
    {
        $supirId = Auth::user()->supir->id; // ambil id user yang login

        $dengan = ['mobil.masterMobil', 'booking.user'];

        // Job aktif (booking dibatalkan tidak dihitung)
        $jobAktif = BookingDetail::with($dengan)->where('supir_id', $supirId)
            ->whereNotIn('status', [0, 3])
            ->get();

        // Riwayat job
        $riwayatJob = BookingDetail::with($dengan)->where('supir_id', $supirId)
            ->where('status', 3)
            ->get();

        // Tawaran terbuka untuk supir ini (cadangan bila popup realtime/Pusher tidak aktif):
        // masih menunggu supir, belum diambil orang lain, dan bookingnya masih aktif.
        $tawaran = JobOffer::where('supir_id', $supirId)
            ->where('status', 0)
            ->whereHas('booking_detail', fn ($q) => $q->whereNull('supir_id')->where('status', Booking::STATUS_BOOKED))
            ->with('booking_detail.mobil.masterMobil')
            ->latest()
            ->get();

        return view('supir.dashboard', compact('supirId', 'jobAktif', 'riwayatJob', 'tawaran'));
    }

    public function updateStatus(Request $request)
    {
        $supir = Auth::user()->supir;

        // Supir yang sedang bertugas (2) tidak boleh menandai dirinya tersedia/libur; status dikembalikan
        // otomatis saat pengembalian mobil dicatat.
        if ((int) $supir->status === 2) {
            return back()->with('error', 'Anda sedang bertugas, status tidak dapat diubah.');
        }

        $supir->status = $request->has('status') ? 1 : 0;
        $supir->save();

        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function acceptJob(Request $request, PenugasanService $service)
    {
        $request->validate(['job_offer_id' => 'required|integer']);

        $supir = Auth::user()->supir;
        abort_unless($supir && (int) $supir->status_approval === 1, 403, 'Akun supir belum disetujui.');

        try {
            $service->terima((int) $request->job_offer_id, $supir);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Job berhasil diterima! 🚗💨');
    }
}
