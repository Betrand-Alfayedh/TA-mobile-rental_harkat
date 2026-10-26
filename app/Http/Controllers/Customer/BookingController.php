<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Mobil;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $bookings = Booking::where('user_id', $userId)
            ->whereIn('status', [1, 2]) // booked & ongoing
            ->with(['details.mobil.masterMobil', 'details.supir.user', 'pembayaranDp', 'pelunasan'])
            ->latest()
            ->get();

        return view('customer.booking.index', compact('bookings'));
    }
    public function create(Request $request)
    {
        $user = Auth::user();
        // mapping asal_kota user ke booking
        // logika asal_kota
        if (strtolower($user->asal_kota) === 'yogyakarta') {
            $asal_kota_booking = 1;
            $nama_kota_booking = null;
            $jaminan_default = 2;
        } else {
            $asal_kota_booking = 2;
            $nama_kota_booking = $user->asal_kota;
            $jaminan_default = 1;
        }
        $mobils = Mobil::approved()->whereNotIn('status', [Mobil::STATUS_RUSAK, Mobil::STATUS_MAINTENANCE])->get();
        if ($mobils->isEmpty()) {
            return redirect()->back()->with('error', 'Belum ada mobil tersedia.');
        }
        return view('customer.booking.create', compact(
            'user',
            'mobils',
            'asal_kota_booking',
            'nama_kota_booking',
            'jaminan_default'
        ));
    }

    // AJAX: cek mobil
    public function checkMobils(Request $request)
    {
        $request->validate([
            'tanggal_sewa' => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_sewa',
        ]);

        $availableMobils = Mobil::with('masterMobil')
            ->freeBetween($request->tanggal_sewa, $request->tanggal_kembali)
            ->get();

        return response()->json([
            'mobils' => $availableMobils
        ]);
    }

    public function store(Request $request, BookingService $service)
    {
        $user = Auth::user();

        // ✅ Cek profile lengkap
        if (!$user->alamat || !$user->no_hp || !$user->asal_kota) {
            return response()->json([
                'status' => 'incomplete_profile'
            ]);
        }

        // ✅ Validasi input
        $validated = $request->validate([
            'mobils'                   => 'required|array|min:1',
            'mobils.*.mobil_id'        => 'required|exists:mobils,id|distinct',
            'mobils.*.tanggal_sewa'    => 'required|date|after:+' . BookingService::MIN_LEAD_HOURS . ' hours',
            'mobils.*.tanggal_kembali' => 'required|date|after:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'     => 'required|in:0,1',
            'asal_kota'                => 'required|in:1,2',
            'nama_kota'                => 'required_if:asal_kota,2|nullable|string',
            'jaminan'                  => 'required|in:1,2',
        ], [
            'mobils.required'                   => 'Pilih minimal satu mobil.',
            'mobils.*.mobil_id.required'        => 'Mobil harus dipilih.',
            'mobils.*.mobil_id.distinct'        => 'Mobil yang sama tidak boleh dipilih dua kali.',
            'mobils.*.tanggal_sewa.required'    => 'Tanggal sewa harus diisi.',
            'mobils.*.tanggal_sewa.after'       => 'Tanggal sewa minimal ' . BookingService::MIN_LEAD_HOURS . ' jam dari sekarang.',
            'mobils.*.tanggal_kembali.after'    => 'Tanggal kembali harus setelah tanggal sewa.',
            'asal_kota.required'                => 'Asal Kota harus diisi.',
            'nama_kota.required_if'             => 'Nama kota harus diisi jika asal kota = luar kota.',
            'jaminan.required'                  => 'Jaminan harus diisi.',
        ]);

        try {
            $service->create($user->id, $validated);
        } catch (\DomainException $e) {
            return back()->withErrors(['mobils' => $e->getMessage()])->withInput();
        }

        return redirect()->route('booking.index')
            ->with('success', 'Booking berhasil! Silakan segera bayar DP sebelum jatuh tempo.');
    }


    // Batalkan booking sendiri (selama DP belum lunas)
    public function cancel(Booking $booking, BookingService $service)
    {
        abort_unless($booking->user_id === Auth::id(), 404);

        if (! $booking->canBeCanceledByCustomer()) {
            return back()->with('error', 'Booking tidak dapat dibatalkan (DP sudah dibayar atau booking sudah berjalan). Hubungi admin.');
        }

        try {
            $service->cancel($booking, 'Dibatalkan oleh customer.');
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }

    // Riwayat booking (sudah selesai)
    public function riwayat()
    {
        $userId = Auth::id();

        $riwayats = Booking::where('user_id', $userId)
            ->whereIn('status', [0, 3]) // canceled & done
            ->with(['details.mobil.masterMobil', 'details.supir.user', 'pembayaranDp', 'pelunasan'])
            ->latest()
            ->get();

        return view('customer.booking.riwayat', compact('riwayats'));
    }
}
