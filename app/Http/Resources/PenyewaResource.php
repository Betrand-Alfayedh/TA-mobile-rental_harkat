<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenyewaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'email'          => $this->email,
            'no_hp'          => $this->no_hp,
            'alamat'         => $this->alamat,
            'asal_kota'      => $this->asal_kota,
            'jumlah_pesanan' => $this->bookings_count ?? null,
        ];
    }
}
