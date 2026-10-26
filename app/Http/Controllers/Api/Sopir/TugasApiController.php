<?php

namespace App\Http\Controllers\Api\Sopir;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanKondisiResource;
use App\Http\Resources\SopirTugasResource;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Services\LaporanKondisiService;
use Illuminate\Http\Request;

class TugasApiController extends Controller
{
    use RespondsJson;

    private const RELASI = ['mobil.masterMobil', 'booking.user', 'laporanKondisis'];

    /** GET /api/sopir/tugas — tugas aktif (booked/ongoing). */
    public function index(Request $request)
    {
        $tugas = BookingDetail::with(self::RELASI)->where('supir_id', $request->user()->supir->id)
            ->whereNotIn('status', [Booking::STATUS_CANCELED, Booking::STATUS_DONE])
            ->orderBy('tanggal_sewa')->get();

        return $this->ok(SopirTugasResource::collection($tugas));
    }

    /** GET /api/sopir/riwayat — tugas selesai. */
    public function riwayat(Request $request)
    {
        $tugas = BookingDetail::with(self::RELASI)->where('supir_id', $request->user()->supir->id)
            ->where('status', Booking::STATUS_DONE)->latest('tanggal_kembali')->get();

        return $this->ok(SopirTugasResource::collection($tugas));
    }

    /** POST /api/sopir/tugas/{id}/laporan-kondisi (multipart: jenis 1|2, kondisi 1|2|3, catatan?, foto?) */
    public function laporan(Request $request, int $id, LaporanKondisiService $service)
    {
        $data = $request->validate([
            'jenis'   => 'required|in:1,2',
            'kondisi' => 'required|in:1,2,3',
            'catatan' => 'nullable|string|max:500',
            'foto'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Hanya unit milik sopir ini yang ditemukan; unit lain dijawab 404 (tidak membocorkan keberadaannya).
        $detail = BookingDetail::where('supir_id', $request->user()->supir->id)->findOrFail($id);

        try {
            $laporan = $service->simpan($detail, $request->user(), $data, $request->file('foto'));
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(new LaporanKondisiResource($laporan), 'Laporan kondisi tersimpan.', 201);
    }
}
