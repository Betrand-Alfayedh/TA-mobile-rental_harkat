<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupirResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'user_id'         => $this->user_id,
            'nama'            => $this->user?->name,
            'email'           => $this->user?->email,
            'no_hp'           => $this->user?->no_hp,
            'alamat'          => $this->user?->alamat,
            'aktif'           => (bool) $this->user?->aktif,
            'status'          => (int) $this->status,
            'status_text'     => $this->status_text,
            'status_approval' => (int) $this->status_approval,
            'gambar'          => $this->gambar ? asset('storage/' . $this->gambar) : null,
        ];
    }
}
