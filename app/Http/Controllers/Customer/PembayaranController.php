<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    public function show(Booking $booking)
    {
        // pastiin booking ini punya pembayaran DP
        $pembayaran = $booking->pembayaranDp()->first();

        if (!$pembayaran) {
            return redirect()->route('booking.index')->with('error', 'Pembayaran DP tidak ditemukan.');
        }

        return view('customer.pembayaran.index', compact('booking', 'pembayaran'));
    }
    public function uploadBukti(Request $request, Booking $booking)
    {
        $request->validate([
            'bukti_bayar' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $pembayaran = $booking->pembayaranDp;
        if (!$pembayaran) {
            abort(404, 'Data pembayaran DP tidak ditemukan.');
        }

        // Upload file
        $path = $request->file('bukti_bayar')->store('bukti_bayar', 'public');

        $pembayaran->update([
            'tanggal_pembayaran' => now(),
            'foto_bukti' => $path,
            'status_pembayaran' => 2, // pending
            'metode_pembayaran' => 3, // qris (default dulu)
        ]);

        return redirect()->route('booking.index')->with('success', 'Bukti pembayaran DP berhasil diupload, tunggu verifikasi admin.');
    }
}
