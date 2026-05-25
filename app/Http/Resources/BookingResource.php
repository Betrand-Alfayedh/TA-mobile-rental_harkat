<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'user_id'               => $this->user_id,
            'tanggal_booking'       => $this->tanggal_booking,
            'tanggal_booking_format' => $this->tanggal_booking_format,
            'asal_kota'             => $this->asal_kota,
            'asal_kota_label'       => $this->asal_kota_label,
            'nama_kota'             => $this->nama_kota,
            'jaminan'               => $this->jaminan,
            'jaminan_label'         => $this->jaminan_label,
            'uang_muka'             => $this->uang_muka,
            'uang_muka_rp'          => $this->uang_muka_rp,
            'total_harga'           => $this->total_harga,
            'total_harga_rp'        => $this->total_harga_rp,
            'status'                => $this->status,
            'status_label'          => $this->status_label,
            'details'               => BookingDetailResource::collection(
                $this->whenLoaded('details')
            ),
            'pembayaran_dp'         => new PembayaranResource(
                $this->whenLoaded('pembayaranDp')
            ),
            'pelunasan'             => new PembayaranResource(
                $this->whenLoaded('pelunasan')
            ),
            'created_at'            => $this->created_at,
        ];
    }
}
