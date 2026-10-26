<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaporanKondisiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'booking_detail_id' => $this->booking_detail_id,
            'jenis'             => (int) $this->jenis,
            'jenis_label'       => $this->jenis_label,
            'kondisi'           => (int) $this->kondisi,
            'kondisi_label'     => $this->kondisi_label,
            'catatan'           => $this->catatan,
            'foto'              => $this->foto ? asset('storage/' . $this->foto) : null,
            'dibuat_oleh'       => $this->whenLoaded('pembuat', fn () => $this->pembuat?->name),
            'dibuat_pada'       => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
