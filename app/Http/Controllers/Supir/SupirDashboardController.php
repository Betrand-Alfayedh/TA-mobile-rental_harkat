<?php

namespace App\Http\Controllers\Supir;

use App\Http\Controllers\Controller;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Supir;
use App\Models\User; // karena role supir ada di users
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupirDashboardController extends Controller
{
    public function index()
    {
        $supirId = Auth::user()->supir->id; // ambil id user yang login

        // Job aktif
        $jobAktif = BookingDetail::where('supir_id', $supirId)
            ->where('status', '!=', 3)
            ->get();

        // Riwayat job
        $riwayatJob = BookingDetail::where('supir_id', $supirId)
            ->where('status', 3)
            ->get();

        return view('supir.dashboard', compact('supirId', 'jobAktif', 'riwayatJob'));
    }

    public function updateStatus(Request $request)
    {
        $supir = Auth::user()->supir;
        $supir->status = $request->has('status') ? 1 : 0;
        $supir->save();

        return back()->with('success', 'Status berhasil diperbarui.');
    }

    public function acceptJob(Request $request, JobOffer $offer)
    {
        $supirId = Auth::user()->supir->id;
        // ambil model supir
        $supir = Auth::user()->supir;
        $jobOffer = JobOffer::where('id', $request->job_offer_id)
            ->where('status', 0) // pastikan masih available
            ->first();

        if (!$jobOffer) {
            return back()->with('error', 'Job sudah tidak tersedia.');
        }

        // update transaksi
        $bookingDtl = BookingDetail::find($jobOffer->booking_detail->id);
        $bookingDtl->pakai_supir = 1;
        $bookingDtl->supir_id = $supirId;
        $bookingDtl->save();

        // update job offer
        $jobOffer->status = 1;
        $jobOffer->save();
        
        $supir->status = 2;
        $supir->save();

        return back()->with('success', 'Job berhasil diterima! 🚗💨');
    }
}
