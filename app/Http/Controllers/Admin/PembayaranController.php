<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Pembayaran;
use App\Notifications\PembayaranDitolakNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PembayaranController extends Controller
{
    public function index()
    {
        $payments = Pembayaran::with(['booking.user'])->latest()->get();
        return view('admin.payments.index', compact('payments'));
    }

    public function verifikasi($id)
    {
        $pembayaran = Pembayaran::with(['booking.user', 'booking.details.mobil'])->findOrFail($id);

        // ✅ Update status pembayaran jadi verified
        $pembayaran->update([
            'status_pembayaran' => 1, // 1 = verified
        ]);

        $booking = $pembayaran->booking;
        $customer = $booking->user; // ini customer (role=4)

        // ✅ Kalau pembayaran ini jenis DP (1), buat otomatis record pelunasan (2)
        if ($pembayaran->jenis == 1) {
            Pembayaran::create([
                'booking_id'        => $booking->id,
                'tanggal_pembayaran' => null, // kosong dulu, nunggu bayar
                'jumlah'            => $booking->total_harga - $pembayaran->jumlah, // sisa yg harus dilunasi
                'metode_pembayaran' => null, // nunggu input dari customer
                'jenis'             => 2, // pelunasan
                'status_pembayaran' => 0, // pending
                'catatan_admin'     => null,
                'foto_bukti'        => null,
                'jatuh_tempo'       => $booking->details->max('tanggal_kembali'), // misalnya jatuh tempo pas pengembalian
            ]);
        }

        // ✅ Generate PDF Invoice
        $pdf = Pdf::loadView('invoices.pdf', compact('booking', 'pembayaran'))->output();

        try {
            // ✅ Kirim email ke customer
            Mail::to($customer->email)->send(new InvoiceMail($booking, $pembayaran, $pdf));
        } catch (\Exception $e) {
            return back()->with('error', 'Pembayaran terverifikasi tapi gagal kirim email: ' . $e->getMessage());
        }

        return back()->with('success', 'Pembayaran diverifikasi & invoice terkirim ke customer.');
    }

    public function tolak(Request $request, Pembayaran $pembayaran)
    {
        $request->validate([
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $pembayaran->update([
            'status_pembayaran' => 0, // ditolak
            'catatan_admin' => $request->catatan_admin,
        ]);

        // ✅ kirim email ke customer
        $pembayaran->booking->user->notify(new PembayaranDitolakNotification($pembayaran));

        return back()->with('error', 'Pembayaran ditolak ❌ dan email notifikasi terkirim.');
    }
}
