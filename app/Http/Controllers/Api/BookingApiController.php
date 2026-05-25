<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Notifications\DpReminderNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingApiController extends Controller
{
    /**
     * List active bookings (status booked & ongoing).
     *
     * GET /api/bookings
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', [1, 2])
            ->with(['details.mobil.masterMobil.tipe', 'pembayaranDp'])
            ->latest()
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => BookingResource::collection($bookings),
        ]);
    }

    /**
     * Create a new booking.
     *
     * POST /api/bookings
     *
     * Body JSON:
     * {
     *   "asal_kota": 1,
     *   "nama_kota": null,
     *   "jaminan": 2,
     *   "mobils": [
     *     {
     *       "mobil_id": 3,
     *       "tanggal_sewa": "2025-07-10 08:00:00",
     *       "tanggal_kembali": "2025-07-12 08:00:00",
     *       "pakai_supir": 0
     *     }
     *   ]
     * }
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Profile completeness check
        if (! $user->alamat || ! $user->no_hp || ! $user->asal_kota) {
            return response()->json([
                'status'  => false,
                'message' => 'Profil belum lengkap. Silakan lengkapi alamat, nomor HP, dan asal kota terlebih dahulu.',
                'code'    => 'INCOMPLETE_PROFILE',
            ], 422);
        }

        $validated = $request->validate([
            'mobils'                     => 'required|array|min:1',
            'mobils.*.mobil_id'          => 'required|exists:mobils,id',
            'mobils.*.tanggal_sewa'      => 'required|date',
            'mobils.*.tanggal_kembali'   => 'required|date|after_or_equal:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'       => 'required|in:0,1',
            'asal_kota'                  => 'required|in:1,2',
            'nama_kota'                  => 'required_if:asal_kota,2|nullable|string',
            'jaminan'                    => 'required|in:1,2',
        ]);

        $booking = Booking::create([
            'user_id'         => $user->id,
            'tanggal_booking' => now(),
            'total_harga'     => 0,
            'uang_muka'       => 0,
            'status'          => Booking::STATUS_BOOKED,
            'asal_kota'       => $request->asal_kota,
            'nama_kota'       => $request->nama_kota,
            'jaminan'         => $request->jaminan,
        ]);

        $totalBooking        = 0;
        $tanggalSewaTerdekat = null;

        foreach ($validated['mobils'] as $item) {
            $mobil          = Mobil::findOrFail($item['mobil_id']);
            $tanggalSewa    = Carbon::parse($item['tanggal_sewa']);
            $tanggalKembali = Carbon::parse($item['tanggal_kembali']);

            $jam  = $tanggalSewa->diffInHours($tanggalKembali);
            $lama = max(1, (int) ceil($jam / 24));

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
                'status'          => Booking::STATUS_BOOKED,
            ]);

            $mobil->update(['status' => Mobil::STATUS_DIBOOKING]);

            $totalBooking += $harga;
            $tanggalSewaTerdekat = is_null($tanggalSewaTerdekat) || $tanggalSewa->lt($tanggalSewaTerdekat)
                ? $tanggalSewa
                : $tanggalSewaTerdekat;
        }

        $booking->update([
            'total_harga' => $totalBooking,
            'uang_muka'   => (int) ($totalBooking / 2),
        ]);

        // Calculate DP due date
        $tanggalBooking    = Carbon::parse($booking->tanggal_booking);
        $jatuhTempoDefault = $tanggalBooking->copy()->addDays(4);
        $jatuhTempo        = $jatuhTempoDefault->min($tanggalSewaTerdekat->copy()->subHours(2));

        $pembayaranDp = Pembayaran::create([
            'booking_id'  => $booking->id,
            'jenis'       => 1, // DP
            'jumlah'      => $booking->uang_muka,
            'status_pembayaran'      => 0,
            'jatuh_tempo' => $jatuhTempo,
        ]);

        // Send email notification
        try {
            $booking->user->notify(new DpReminderNotification($booking, $pembayaranDp));
        } catch (\Exception $e) {
            \Log::warning('Email DP notification failed: ' . $e->getMessage());
        }

        $booking->load(['details.mobil.masterMobil.tipe', 'pembayaranDp']);

        return response()->json([
            'status'  => true,
            'message' => 'Booking berhasil dibuat! Segera bayar DP sebelum jatuh tempo.',
            'data'    => new BookingResource($booking),
        ], 201);
    }

    /**
     * Show a single booking.
     *
     * GET /api/bookings/{id}
     */
    public function show(Request $request, int $id)
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->with(['details.mobil.masterMobil.tipe', 'pembayaranDp', 'pelunasan'])
            ->findOrFail($id);

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => new BookingResource($booking),
        ]);
    }

    /**
     * Booking history (done & canceled).
     *
     * GET /api/bookings/riwayat
     */
    public function riwayat(Request $request)
    {
        $riwayats = Booking::where('user_id', $request->user()->id)
            ->whereIn('status', [0, 3])
            ->with(['details.mobil.masterMobil.tipe', 'pembayaranDp'])
            ->latest()
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => BookingResource::collection($riwayats),
        ]);
    }

    /**
     * Price estimation before submitting booking.
     *
     * POST /api/bookings/estimate
     */
    public function estimate(Request $request)
    {
        $request->validate([
            'mobils'                   => 'required|array|min:1',
            'mobils.*.mobil_id'        => 'required|exists:mobils,id',
            'mobils.*.tanggal_sewa'    => 'required|date',
            'mobils.*.tanggal_kembali' => 'required|date|after_or_equal:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'     => 'required|in:0,1',
        ]);

        $items = [];
        $total = 0;

        foreach ($request->mobils as $item) {
            $mobil          = Mobil::findOrFail($item['mobil_id']);
            $tanggalSewa    = Carbon::parse($item['tanggal_sewa']);
            $tanggalKembali = Carbon::parse($item['tanggal_kembali']);
            $jam            = $tanggalSewa->diffInHours($tanggalKembali);
            $lama           = max(1, (int) ceil($jam / 24));
            $harga          = $item['pakai_supir']
                ? $mobil->harga_all_in * $lama
                : $mobil->harga_sewa * $lama;

            $items[] = [
                'mobil_id'   => $mobil->id,
                'merk'       => $mobil->merk,
                'lama_sewa'  => $lama,
                'harga_unit' => $item['pakai_supir'] ? $mobil->harga_all_in : $mobil->harga_sewa,
                'subtotal'   => $harga,
            ];
            $total += $harga;
        }

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => [
                'items'     => $items,
                'total'     => $total,
                'uang_muka' => (int) ($total / 2),
            ],
        ]);
    }
}
