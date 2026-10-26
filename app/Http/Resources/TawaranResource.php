<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tawaran tugas untuk sopir (tanpa data keuangan). */
class TawaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $d = $this->booking_detail;

        return [
            'id'              => $this->id,
            'status'          => (int) $this->status,
            'ditawarkan_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'unit'            => $d ? (new SopirTugasResource($d))->toArray($request) : null,
        ];
    }
}
