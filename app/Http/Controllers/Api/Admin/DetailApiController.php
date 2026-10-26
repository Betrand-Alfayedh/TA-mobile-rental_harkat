<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminDetailResource;
use App\Models\BookingDetail;
use App\Models\Pengembalian;
use App\Services\JemputService;
use App\Services\PengembalianService;
use App\Services\PenugasanService;
use Illuminate\Http\Request;

/** Aksi per unit sewa (booking detail): penugasan sopir, jemput, pengembalian. */
class DetailApiController extends Controller
{
    use RespondsJson;

    private const RELASI = ['mobil.masterMobil.tipe', 'supir.user', 'jobOffers', 'pengembalian', 'laporanKondisis'];

    /** POST /api/admin/detail/{detail}/penugasan */
    public function penugasan(BookingDetail $detail, PenugasanService $service)
    {
        try {
            $jumlah = $service->tawarkan($detail);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(
            ['jumlah_sopir' => $jumlah, 'detail' => new AdminDetailResource($detail->fresh(self::RELASI))],
            "Tugas ditawarkan ke {$jumlah} sopir."
        );
    }

    /** PATCH /api/admin/detail/{detail}/jemput */
    public function jemput(BookingDetail $detail, JemputService $service)
    {
        try {
            $service->konfirmasi($detail);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(new AdminDetailResource($detail->fresh(self::RELASI)), 'Unit dijemput.');
    }

    /** POST /api/admin/detail/{detail}/pengembalian  (multipart: tanggal_kembali_aktual, kondisi 1 baik|0 rusak, catatan?, gambar?) */
    public function pengembalian(BookingDetail $detail, Request $request, PengembalianService $service)
    {
        $request->validate([
            'tanggal_kembali_aktual' => 'required|date',
            'kondisi'                => 'required|in:0,1',
            'catatan'                => 'nullable|string|max:500',
            'gambar'                 => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $p = $service->catat(
                $detail->id,
                $request->only(['tanggal_kembali_aktual', 'kondisi', 'catatan']),
                $request->file('gambar'),
                $request->user()
            );
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        $detail = $detail->fresh(array_merge(self::RELASI, ['booking']));

        return $this->ok([
            'jam_terlambat'  => $p->denda_flag ? (int) ($p->nominal_denda / Pengembalian::DENDA_PER_JAM) : 0,
            'nominal_denda'  => (int) $p->nominal_denda,
            'status_pesanan' => $detail->booking->status_label,
            'detail'         => new AdminDetailResource($detail),
        ], 'Pengembalian dicatat.', 201);
    }
}
