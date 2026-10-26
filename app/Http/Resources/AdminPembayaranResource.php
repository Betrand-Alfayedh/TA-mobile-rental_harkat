<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Pembayaran untuk admin: + penyewa dan total pesanan. */
class AdminPembayaranResource extends PembayaranResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'penyewa'      => $this->whenLoaded('booking', fn () => $this->booking?->user?->name),
            'total_pesanan' => $this->whenLoaded('booking', fn () => (int) $this->booking?->total_harga),
            'status_pesanan' => $this->whenLoaded('booking', fn () => $this->booking?->status_label),
        ]);
    }
}
