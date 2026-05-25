<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PembayaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'booking_id'            => $this->booking_id,
            'jenis'                 => $this->jenis,
            'jenis_text'            => $this->jenis_text,
            'jumlah'                => $this->jumlah,
            'metode_pembayaran'     => $this->metode_pembayaran,
            'metode_pembayaran_text' => $this->metode_pembayaran_text,
            'status_pembayaran'     => $this->status_pembayaran,
            'status_pembayaran_text' => $this->status_pembayaran_text,
            'foto_bukti'            => $this->foto_bukti
                ? asset('storage/' . $this->foto_bukti)
                : null,
            'tanggal_pembayaran'    => $this->tanggal_pembayaran,
            'jatuh_tempo'           => $this->jatuh_tempo,
            'catatan_admin'         => $this->catatan_admin,
            'keterangan'            => $this->keterangan,
        ];
    }
}
