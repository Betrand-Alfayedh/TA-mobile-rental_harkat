<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Notifications\DpReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $bookings = Booking::where('user_id', $userId)
            ->whereIn('status', [1, 2]) // booked & ongoing
            ->with(['details', 'pembayaranDp'])
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
        $mobils = Mobil::where('status', 1)->where('status_approval', 1)->get();
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

        $start = $request->tanggal_sewa;
        $end = $request->tanggal_kembali;

        // Cari mobil yang bentrok
        $bookedMobilIds = BookingDetail::where(function ($q) use ($start, $end) {
            $q->where(function ($query) use ($start, $end) {
                // booking mulai sebelum end dan selesai setelah start = overlap
                $query->where('tanggal_sewa', '<=', $end)
                    ->where('tanggal_kembali', '>=', $start);
            });
        })
            ->pluck('mobil_id')
            ->toArray();

        // Ambil mobil yang nggak bentrok
        $availableMobils = Mobil::whereNotIn('id', $bookedMobilIds)->get();

        return response()->json([
            'mobils' => $availableMobils
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // ✅ Cek profile lengkap
        if (!$user->alamat || !$user->no_hp || !$user->asal_kota) {
            return response()->json([
                'status' => 'incomplete_profile'
            ]);
        }

        if (!$user) {
            abort(403, 'Anda harus login dulu');
        }

        // ✅ Validasi input
        $validated = $request->validate([
            'mobils.*.mobil_id'        => 'required|exists:mobils,id',
            'mobils.*.tanggal_sewa'    => 'required|date',
            'mobils.*.tanggal_kembali' => 'required|date|after_or_equal:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'     => 'required|in:0,1',
            'asal_kota'                => 'required|in:1,2',
            'nama_kota'                => 'required_if:asal_kota,2|nullable|string',
            'jaminan'                  => 'required|in:1,2',
        ], [
            'mobils.*.mobil_id.required'        => 'Mobil harus dipilih.',
            'mobils.*.tanggal_sewa.required'    => 'Tanggal sewa harus diisi.',
            'mobils.*.tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal sewa.',
            'asal_kota.required'                => 'Asal Kota harus diisi.',
            'nama_kota.required_if'             => 'Nama kota harus diisi jika asal kota = luar kota.',
            'jaminan.required'                  => 'Jaminan harus diisi.',
        ]);

        // ✅ Buat booking baru
        $booking = Booking::create([
            'user_id'        => $user->id,
            'tanggal_booking' => now(),
            'total_harga'    => 0,
            'uang_muka'    => 0,
            'status'         => 1, // booked
            'asal_kota'      => $request->asal_kota,
            'nama_kota'      => $request->nama_kota,
            'jaminan'        => $request->jaminan,
        ]);

        $totalBooking = 0;
        $tanggalSewaTerdekat = null;

        // ✅ Simpan detail booking
        foreach ($validated['mobils'] as $item) {
            $mobil          = Mobil::findOrFail($item['mobil_id']);
            $tanggalSewa    = Carbon::parse($item['tanggal_sewa']);
            $tanggalKembali = Carbon::parse($item['tanggal_kembali']);

            // hitung selisih jam -> dibagi 24 -> ceil (dibulatkan ke atas)
            $jam = $tanggalSewa->diffInHours($tanggalKembali);
            $lama = max(1, ceil($jam / 24));


            $harga = $item['pakai_supir']
                ? $mobil->harga_all_in * $lama
                : $mobil->harga_sewa * $lama;


            BookingDetail::create([
                'booking_id'      => $booking->id,
                'mobil_id'        => $mobil->id,
                'pakai_supir'     => $item['pakai_supir'],
                'supir_id'        => null,
                'tanggal_sewa'    => $tanggalSewa,
                'tanggal_kembali' => $tanggalKembali,
                'lama_sewa'       => $lama,
                'harga'           => $harga,
            ]);

            // update mobil jadi booked
            foreach ($validated['mobils'] as $item) {
                $mobil = Mobil::findOrFail($item['mobil_id']);
                $mobil->update(['status' => 2]);
                \Log::info("Mobil {$mobil->id} status updated to {$mobil->status}");
            }
            $totalBooking += $harga;

            // simpan tanggal sewa paling awal
            $tanggalSewaTerdekat = is_null($tanggalSewaTerdekat) || $tanggalSewa->lt($tanggalSewaTerdekat)
                ? $tanggalSewa
                : $tanggalSewaTerdekat;
        }

        // ✅ Update total booking & uang muka
        $booking->update([
            'total_harga' => $totalBooking,
            'uang_muka'   => $totalBooking / 2, // override biar fix 50%
        ]);

        // ✅ Hitung jatuh tempo DP
        $tanggalBooking = Carbon::parse($booking->tanggal_booking);

        // default jatuh tempo = 3 hari setelah booking
        $jatuhTempoDefault = $tanggalBooking->copy()->addDays(4);

        // jatuh tempo final = minimal dari (jatuh tempo default, tanggal sewa - 2 jam)
        $jatuhTempo = $jatuhTempoDefault->min($tanggalSewaTerdekat->copy()->subHours(2));

        // ✅ Generate pembayaran DP
        $pembayaranDp = Pembayaran::create([
            'booking_id'  => $booking->id,
            'jenis'       => 1, // DP
            'jumlah'      => $booking->uang_muka,
            'status'      => 0, // belum dibayar
            'jatuh_tempo' => $jatuhTempo,
        ]);
        // ✅ Kirim notifikasi email DP
        $booking->user->notify(new DpReminderNotification($booking, $pembayaranDp));

        return redirect()->route('booking.index')
            ->with('success', 'Booking berhasil! Silakan segera bayar DP sebelum jatuh tempo.');
    }


    // Riwayat booking (sudah selesai)
    public function riwayat()
    {
        $userId = Auth::id();

        $riwayats = Booking::where('user_id', $userId)
            ->whereIn('status', [0, 3]) // canceled & done
            ->with(['details', 'pembayaranDp'])
            ->latest()
            ->get();

        return view('customer.booking.riwayat', compact('riwayats'));
    }
}
