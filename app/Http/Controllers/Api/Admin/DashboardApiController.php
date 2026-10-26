<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Supir;

class DashboardApiController extends Controller
{
    use RespondsJson;

    /** GET /api/admin/dashboard — angka ringkas untuk beranda admin. */
    public function index()
    {
        $aktif = fn () => BookingDetail::where('status', Booking::STATUS_BOOKED)
            ->whereHas('booking', fn ($b) => $b->where('status', Booking::STATUS_BOOKED));

        return $this->ok([
            'pesanan' => [
                'masuk_hari_ini' => Booking::whereDate('created_at', today())->count(),
                'booked'         => Booking::where('status', Booking::STATUS_BOOKED)->count(),
                'berjalan'       => Booking::where('status', Booking::STATUS_ONGOING)->count(),
                'selesai'        => Booking::where('status', Booking::STATUS_DONE)->count(),
                'dibatalkan'     => Booking::where('status', Booking::STATUS_CANCELED)->count(),
            ],
            'perlu_tindakan' => [
                'pembayaran_menunggu_verifikasi' => Pembayaran::where('status_pembayaran', 2)->count(),
                'perlu_penugasan_sopir' => $aktif()->where('pakai_supir', '>', 0)->whereNull('supir_id')
                    ->whereDoesntHave('jobOffers', fn ($o) => $o->where('status', 0))->count(),
                'jemput_hari_ini'       => $aktif()->whereDate('tanggal_sewa', today())->count(),
                'kembali_hari_ini'      => BookingDetail::where('status', Booking::STATUS_ONGOING)->whereDate('tanggal_kembali', today())->count(),
                'terlambat_kembali'     => BookingDetail::where('status', Booking::STATUS_ONGOING)->where('tanggal_kembali', '<', now())->count(),
            ],
            'armada' => [
                'total'       => Mobil::count(),
                'tersedia'    => Mobil::where('status', Mobil::STATUS_TERSEDIA)->count(),
                'disewa'      => Mobil::where('status', Mobil::STATUS_DISEWA)->count(),
                'maintenance' => Mobil::where('status', Mobil::STATUS_MAINTENANCE)->count(),
                'rusak'       => Mobil::where('status', Mobil::STATUS_RUSAK)->count(),
            ],
            'sopir' => [
                'tersedia' => Supir::where('status', 1)->count(),
                'bertugas' => Supir::where('status', 2)->count(),
                'libur'    => Supir::where('status', 0)->count(),
            ],
        ]);
    }
}
