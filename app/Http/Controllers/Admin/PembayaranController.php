<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Services\PembayaranService;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    public function index()
    {
        $payments = Pembayaran::with(['booking.user'])->latest()->get();
        return view('admin.payments.index', compact('payments'));
    }

    public function verifikasi($id, PembayaranService $service)
    {
        $pembayaran = Pembayaran::with(['booking.user', 'booking.details.mobil'])->findOrFail($id);

        return $this->lunaskan(fn () => $service->verifikasi($pembayaran, auth()->user()), $service);
    }

    /** Admin menerima pembayaran langsung (mis. tunai saat jemput/kembali) tanpa bukti upload. */
    public function tandaiLunas(Request $request, Pembayaran $pembayaran, PembayaranService $service)
    {
        $request->validate(['metode_pembayaran' => 'required|in:1,2,3']);

        return $this->lunaskan(
            fn () => $service->tandaiLunas($pembayaran, (int) $request->metode_pembayaran, auth()->user()),
            $service
        );
    }

    /** Jalankan aksi pelunasan lalu kirim invoice; galat aturan bisnis ditampilkan sebagai pesan. */
    private function lunaskan(\Closure $aksi, PembayaranService $service)
    {
        try {
            $pembayaran = $aksi();
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($galat = $service->kirimInvoice($pembayaran)) {
            return back()->with('error', 'Pembayaran tercatat lunas tapi gagal kirim email: ' . $galat);
        }

        return back()->with('success', 'Pembayaran dicatat lunas & invoice terkirim ke customer.');
    }

    public function tolak(Request $request, Pembayaran $pembayaran, PembayaranService $service)
    {
        $request->validate([
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        try {
            $service->tolak($pembayaran, $request->catatan_admin, auth()->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('error', 'Pembayaran ditolak ❌ dan email notifikasi terkirim.');
    }
}
