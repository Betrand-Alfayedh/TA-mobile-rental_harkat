<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobilResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'plat_nomor'      => $this->plat_nomor,
            'merk'            => $this->merk,
            'tahun'           => $this->tahun,
            'harga_sewa'      => $this->harga_sewa,
            'harga_all_in'    => $this->harga_all_in,
            'status'          => $this->status,
            'status_text'     => $this->status_text,
            'gambar'          => $this->gambar
                ? asset('storage/' . $this->gambar)
                : null,
            'master_mobil'    => $this->whenLoaded('masterMobil', fn() => [
                'id'       => $this->masterMobil->id,
                'nama'     => $this->masterMobil->nama,
                'tipe'     => $this->masterMobil->tipe
                    ? [
                        'id'        => $this->masterMobil->tipe->id,
                        'nama_tipe' => $this->masterMobil->tipe->nama_tipe,
                    ]
                    : null,
            ]),
            'images'          => $this->whenLoaded('images', fn() =>
                $this->images->map(fn($img) => asset('storage/' . $img->path))
            ),
        ];
    }
}
