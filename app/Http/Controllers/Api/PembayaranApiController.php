<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PembayaranResource;
use App\Models\Booking;
use App\Models\Pembayaran;
use Illuminate\Http\Request;

class PembayaranApiController extends Controller
{
    /**
     * Get DP payment details for a booking.
     *
     * GET /api/bookings/{booking}/pembayaran
     */
    public function show(Request $request, int $bookingId)
    {
        $booking    = Booking::where('user_id', $request->user()->id)->findOrFail($bookingId);
        $pembayaran = $booking->pembayaranDp()->first();

        if (! $pembayaran) {
            return response()->json([
                'status'  => false,
                'message' => 'Data pembayaran DP tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => new PembayaranResource($pembayaran),
        ]);
    }

    /**
     * Upload payment proof (bukti bayar).
     *
     * POST /api/bookings/{booking}/pembayaran/upload
     *
     * Multipart form-data with field: bukti_bayar (image file)
     * Optional field: metode_pembayaran (1=Cash, 2=Transfer, 3=QRIS, default=3)
     */
    public function uploadBukti(Request $request, int $bookingId)
    {
        $booking = Booking::where('user_id', $request->user()->id)->findOrFail($bookingId);

        $request->validate([
            'bukti_bayar'       => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'metode_pembayaran' => 'nullable|in:1,2,3',
        ]);

        $pembayaran = $booking->pembayaranDp;
        if (! $pembayaran) {
            return response()->json([
                'status'  => false,
                'message' => 'Data pembayaran DP tidak ditemukan.',
            ], 404);
        }

        if ($pembayaran->status_pembayaran === 1) {
            return response()->json([
                'status'  => false,
                'message' => 'Pembayaran sudah dikonfirmasi oleh admin.',
            ], 422);
        }

        $path = $request->file('bukti_bayar')->store('bukti_bayar', 'public');

        $pembayaran->update([
            'tanggal_pembayaran' => now(),
            'foto_bukti'         => $path,
            'status_pembayaran'  => 2, // pending verification
            'metode_pembayaran'  => $request->input('metode_pembayaran', 3),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.',
            'data'    => new PembayaranResource($pembayaran->fresh()),
        ]);
    }

    /**
     * Get all payments for a booking (DP + pelunasan).
     *
     * GET /api/bookings/{booking}/semua-pembayaran
     */
    public function allPayments(Request $request, int $bookingId)
    {
        $booking  = Booking::where('user_id', $request->user()->id)->findOrFail($bookingId);
        $payments = Pembayaran::where('booking_id', $booking->id)->get();

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => PembayaranResource::collection($payments),
        ]);
    }
}
