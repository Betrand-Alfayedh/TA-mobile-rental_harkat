<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Unit sewa untuk admin: + sopir, status tawaran, dan pengembalian. */
class AdminDetailResource extends BookingDetailResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'supir' => $this->whenLoaded('supir', fn () => $this->supir ? [
                'id'     => $this->supir->id,
                'nama'   => $this->supir->user?->name,
                'no_hp'  => $this->supir->user?->no_hp,
                'status' => (int) $this->supir->status,
            ] : null),
            'laporan_sebelum' => $this->whenLoaded('laporanKondisis', fn () => ($l = $this->laporanKondisis->firstWhere('jenis', 1)) ? new LaporanKondisiResource($l) : null),
            'laporan_sesudah' => $this->whenLoaded('laporanKondisis', fn () => ($l = $this->laporanKondisis->firstWhere('jenis', 2)) ? new LaporanKondisiResource($l) : null),
            'menunggu_supir' => $this->whenLoaded('jobOffers', fn () => $this->menunggu_supir),
            'pengembalian'   => $this->whenLoaded('pengembalian', fn () => $this->pengembalian ? [
                'tanggal_kembali_aktual' => $this->pengembalian->tanggal_kembali_aktual,
                'kondisi'                => (int) $this->pengembalian->kondisi,
                'kondisi_label'          => $this->pengembalian->kondisi_label,
                'denda_flag'             => (bool) $this->pengembalian->denda_flag,
                'nominal_denda'          => (int) $this->pengembalian->nominal_denda,
                'catatan'                => $this->pengembalian->catatan,
                'gambar'                 => $this->pengembalian->gambar ? asset('storage/' . $this->pengembalian->gambar) : null,
            ] : null),
        ]);
    }
}
