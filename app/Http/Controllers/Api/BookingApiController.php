<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Mobil;
use App\Services\BookingService;
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
        // Aktif = booked/ongoing, plus booking selesai yang pelunasannya belum dibayar (supaya masih bisa dibayar).
        $bookings = Booking::where('user_id', $request->user()->id)
            ->where(fn ($q) => $q->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_ONGOING])
                ->orWhere(fn ($d) => $d->where('status', Booking::STATUS_DONE)
                    ->whereHas('pelunasan', fn ($p) => $p->where('status_pembayaran', '!=', 1))))
            ->with(['details.mobil.masterMobil.tipe', 'pembayaranDp', 'pelunasan'])
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
    public function store(Request $request, BookingService $service)
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
            'mobils.*.mobil_id'          => 'required|exists:mobils,id|distinct',
            'mobils.*.tanggal_sewa'      => 'required|date|after:+' . BookingService::MIN_LEAD_HOURS . ' hours',
            'mobils.*.tanggal_kembali'   => 'required|date|after:mobils.*.tanggal_sewa',
            'mobils.*.pakai_supir'       => 'required|in:0,1',
            'asal_kota'                  => 'required|in:1,2',
            'nama_kota'                  => 'required_if:asal_kota,2|nullable|string',
            'jaminan'                    => 'required|in:1,2',
        ], [
            'mobils.*.tanggal_sewa.after'    => 'Tanggal sewa minimal ' . BookingService::MIN_LEAD_HOURS . ' jam dari sekarang.',
            'mobils.*.tanggal_kembali.after' => 'Tanggal kembali harus setelah tanggal sewa.',
            'mobils.*.mobil_id.distinct'     => 'Mobil yang sama tidak boleh dipilih dua kali.',
        ]);

        try {
            $booking = $service->create($user->id, $validated);
        } catch (\DomainException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

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
     * Batalkan booking milik sendiri (selama DP belum lunas).
     *
     * POST /api/bookings/{id}/cancel
     */
    public function cancel(Request $request, int $id, BookingService $service)
    {
        $booking = Booking::where('user_id', $request->user()->id)->with('pembayaranDp')->findOrFail($id);

        if (! $booking->canBeCanceledByCustomer()) {
            return response()->json([
                'status'  => false,
                'message' => 'Booking tidak dapat dibatalkan dari aplikasi (DP sudah dibayar atau booking sudah berjalan). Hubungi admin.',
            ], 422);
        }

        try {
            $service->cancel($booking, 'Dibatalkan oleh customer.');
        } catch (\DomainException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'message' => 'Booking berhasil dibatalkan.']);
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
            ->with(['details.mobil.masterMobil.tipe', 'pembayaranDp', 'pelunasan'])
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
