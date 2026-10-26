<?php

namespace App\Http\Controllers\Api\Sopir;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    use RespondsJson;

    /** GET /api/sopir/dashboard */
    public function index(Request $request)
    {
        $supir = $request->user()->supir;

        return $this->ok([
            'sopir'         => ['id' => $supir->id, 'nama' => $request->user()->name, 'status' => (int) $supir->status, 'status_text' => $supir->status_text],
            'tugas_aktif'   => BookingDetail::where('supir_id', $supir->id)->whereNotIn('status', [Booking::STATUS_CANCELED, Booking::STATUS_DONE])->count(),
            'tawaran_baru'  => $this->tawaranTerbuka($supir->id)->count(),
            'tugas_selesai' => BookingDetail::where('supir_id', $supir->id)->where('status', Booking::STATUS_DONE)->count(),
        ]);
    }

    /** PATCH /api/sopir/status  { status: 1 tersedia | 0 libur } — dikunci saat bertugas. */
    public function status(Request $request)
    {
        $request->validate(['status' => 'required|in:0,1']);
        $supir = $request->user()->supir;

        if ((int) $supir->status === 2) {
            return $this->fail('Anda sedang bertugas, status tidak dapat diubah.');
        }

        $supir->update(['status' => (int) $request->status]);

        return $this->ok(['status' => (int) $supir->status, 'status_text' => $supir->status_text], 'Status diperbarui.');
    }

    /** Tawaran yang masih relevan: terbuka, unit belum bersopir, dan pesanan masih Booked. */
    public static function tawaranTerbuka(int $supirId)
    {
        return JobOffer::where('supir_id', $supirId)->where('status', 0)
            ->whereHas('booking_detail', fn ($q) => $q->whereNull('supir_id')->where('status', Booking::STATUS_BOOKED));
    }
}
