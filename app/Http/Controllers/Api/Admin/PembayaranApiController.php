<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminPembayaranResource;
use App\Models\Pembayaran;
use App\Services\PembayaranService;
use Illuminate\Http\Request;

/** Verifikasi dan pencatatan pembayaran oleh admin. */
class PembayaranApiController extends Controller
{
    use RespondsJson;

    /** GET /api/admin/pembayaran?status=0|1|2&jenis=1|2 — status 2 = menunggu verifikasi */
    public function index(Request $request)
    {
        $request->validate([
            'status'   => 'nullable|in:0,1,2',
            'jenis'    => 'nullable|in:1,2',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $p = Pembayaran::with('booking.user')
            ->when($request->filled('status'), fn ($q) => $q->where('status_pembayaran', $request->status))
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->jenis))
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return $this->page($p, AdminPembayaranResource::class);
    }

    /** POST /api/admin/pembayaran/{id}/verifikasi */
    public function verifikasi(int $id, Request $request, PembayaranService $service)
    {
        $pembayaran = Pembayaran::with(['booking.user', 'booking.details.mobil'])->findOrFail($id);

        return $this->lunaskan(fn () => $service->verifikasi($pembayaran, $request->user()), $service);
    }

    /** POST /api/admin/pembayaran/{id}/lunas  { metode_pembayaran: 1 tunai | 2 transfer | 3 QRIS } */
    public function lunas(int $id, Request $request, PembayaranService $service)
    {
        $request->validate(['metode_pembayaran' => 'required|in:1,2,3']);
        $pembayaran = Pembayaran::with(['booking.user', 'booking.details.mobil'])->findOrFail($id);

        return $this->lunaskan(
            fn () => $service->tandaiLunas($pembayaran, (int) $request->metode_pembayaran, $request->user()),
            $service
        );
    }

    /** POST /api/admin/pembayaran/{id}/tolak  { catatan_admin? } */
    public function tolak(int $id, Request $request, PembayaranService $service)
    {
        $request->validate(['catatan_admin' => 'nullable|string|max:500']);
        $pembayaran = Pembayaran::with(['booking.user', 'booking.details'])->findOrFail($id);

        try {
            $service->tolak($pembayaran, $request->catatan_admin, $request->user());
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(new AdminPembayaranResource($pembayaran->fresh('booking.user')), 'Pembayaran ditolak; pelanggan dapat mengunggah ulang.');
    }

    /** Lunaskan lalu kirim invoice. Gagal kirim invoice tidak membatalkan pelunasan. */
    private function lunaskan(\Closure $aksi, PembayaranService $service)
    {
        try {
            $pembayaran = $aksi();
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $galat = $service->kirimInvoice($pembayaran);

        return $this->ok(
            ['pembayaran' => new AdminPembayaranResource($pembayaran->fresh('booking.user')), 'invoice_terkirim' => $galat === null],
            $galat === null ? 'Pembayaran dicatat lunas dan invoice terkirim.' : 'Pembayaran dicatat lunas, tetapi invoice gagal dikirim.'
        );
    }
}
