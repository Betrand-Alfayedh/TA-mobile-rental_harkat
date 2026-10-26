<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** Booking untuk admin: pesanan + data penyewa + semua pembayaran + unit dengan sopir/pengembalian. */
class AdminBookingResource extends BookingResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'penyewa' => $this->whenLoaded('user', fn () => [
                'id'        => $this->user->id,
                'name'      => $this->user->name,
                'email'     => $this->user->email,
                'no_hp'     => $this->user->no_hp,
                'alamat'    => $this->user->alamat,
                'asal_kota' => $this->user->asal_kota,
            ]),
            'pembayarans' => PembayaranResource::collection($this->whenLoaded('pembayarans')),
            'details'     => AdminDetailResource::collection($this->whenLoaded('details')),
        ]);
    }
}
