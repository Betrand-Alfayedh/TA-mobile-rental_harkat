<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Unit sewa untuk sopir: info perjalanan dan penyewa, tanpa harga/pembayaran. */
class SopirTugasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $booking = $this->booking;
        $laporan = $this->relationLoaded('laporanKondisis') ? $this->laporanKondisis->keyBy('jenis') : collect();

        return [
            'id'              => $this->id,
            'status'          => (int) $this->status,
            'status_label'    => $this->status_label,
            'mobil'           => $this->mobil ? [
                'id'         => $this->mobil->id,
                'nama'       => $this->mobil->masterMobil?->nama,
                'merk'       => $this->mobil->merk,
                'plat_nomor' => $this->mobil->plat_nomor,
                'gambar'     => $this->mobil->gambar ? asset('gambar_mobil/' . $this->mobil->gambar) : null,
            ] : null,
            'tanggal_sewa'    => $this->tanggal_sewa,
            'tanggal_kembali' => $this->tanggal_kembali,
            'lama_sewa'       => $this->lama_sewa,
            'penyewa'         => $booking?->user ? ['nama' => $booking->user->name, 'no_hp' => $booking->user->no_hp] : null,
            'asal_kota'       => $booking?->asal_kota_label,
            'laporan_sebelum' => $laporan->has(1) ? new LaporanKondisiResource($laporan[1]) : null,
            'laporan_sesudah' => $laporan->has(2) ? new LaporanKondisiResource($laporan[2]) : null,
        ];
    }
}
