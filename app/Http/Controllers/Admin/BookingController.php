<?php

namespace App\Http\Controllers\Admin;

use App\Events\JobAssigned;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransaksiRequest;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Supir;
use App\Models\User;
use App\Notifications\JobAssignedEmail;
use App\Services\BookingService;
use App\Services\JemputService;
use App\Services\PenugasanService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|in:0,1,2,3',
        ]);

        // Cari menurut nomor pesanan atau nama pelanggan; saring menurut status pesanan.
        $q = trim((string) $request->q, " #");
        $bookings = Booking::with(['user', 'details'])
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
                if (ctype_digit($q)) {
                    $w->orWhere('id', (int) $q);
                }
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', (int) $request->status))
            ->latest()
            ->get();

        return view('admin.transaksi.index', compact('bookings'));
    }

    public function create()
    {
        $pelanggans = User::where('role', 4)->get();
        $mobils = Mobil::with('masterMobil')->where('status', 1)->where('status_approval', 1)->get();
        if ($mobils->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada mobil tersedia.');
        }
        $supirs = Supir::all();

        return view('admin.transaksi.create', compact('pelanggans', 'mobils', 'supirs'));
    }

    public function store(TransaksiRequest $request, BookingService $service): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $service->create((int) $validated['pelanggan_id'], $validated);
        } catch (\DomainException $e) {
            return back()->withErrors(['mobils' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.booking.index')->with('success', 'Transaksi berhasil dibuat.');
    }

    public function konfirmasiJemput(BookingDetail $detail, JemputService $service)
    {
        try {
            $service->konfirmasi($detail);
        } catch (\DomainException $e) {
            return request()->wantsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 400)
                : back()->with('error', $e->getMessage());
        }

        $mobil        = $detail->mobil;
        $customerName = $detail->booking->user->name ?? '-';
        $mobilName    = $mobil->masterMobil->nama ?? '-';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Mobil $mobilName berhasil dijemput oleh $customerName!",
                'mobil_status_text' => $mobil->status_text,
                'mobil_status_badge_class' => $mobil->status_badge_class
            ]);
        }

        return back()->with('successSwal', "Mobil $mobilName berhasil dijemput oleh $customerName!");
    }


    public function cancel(Request $request, Booking $booking, BookingService $service)
    {
        $request->validate(['catatan' => 'nullable|string|max:200']);

        $reason = 'Dibatalkan oleh admin.' . ($request->filled('catatan') ? ' ' . $request->catatan : '');

        try {
            $service->cancel($booking, $reason);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Booking #' . $booking->id . ' dibatalkan dan customer diberi tahu lewat email.');
    }

    public function assignJobSupir(Request $request, BookingDetail $bookingDtl, PenugasanService $service)
    {
        try {
            $jumlah = $service->tawarkan($bookingDtl);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('AssignJob error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Gagal assign job: ' . $e->getMessage());
        }

        return back()->with('success', "Job dikirim ke $jumlah supir available.");
    }
}
