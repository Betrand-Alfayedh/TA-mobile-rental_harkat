<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Tabel booking_details tidak punya kolom harga: hitung dari harga mobil x lama sewa.
        // ponytail: pakai harga mobil saat ini; simpan kolom harga jika harga sering berubah.
        $harga = $this->relationLoaded('mobil') && $this->mobil
            ? $this->lama_sewa * $this->mobil->getHargaPerHari((bool) $this->pakai_supir)
            : 0;

        return [
            'id'              => $this->id,
            'mobil'           => new MobilResource($this->whenLoaded('mobil')),
            'pakai_supir'     => (bool) $this->pakai_supir,
            'supir_id'        => $this->supir_id,
            'tanggal_sewa'    => $this->tanggal_sewa,
            'tanggal_kembali' => $this->tanggal_kembali,
            'lama_sewa'       => $this->lama_sewa,
            'harga'           => $harga,
            'status'          => $this->status,
            'status_label'    => $this->status_label,
            'tanggal_mulai_format'   => $this->tanggal_mulai_format,
            'tanggal_selesai_format' => $this->tanggal_selesai_format,
        ];
    }
}
