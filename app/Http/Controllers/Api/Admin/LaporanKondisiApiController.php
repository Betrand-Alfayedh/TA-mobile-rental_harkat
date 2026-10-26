<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\LaporanKondisiResource;
use App\Models\BookingDetail;
use App\Services\LaporanKondisiService;
use Illuminate\Http\Request;

/** Laporan kondisi kendaraan oleh admin (mis. sewa lepas kunci yang tidak punya sopir). */
class LaporanKondisiApiController extends Controller
{
    use RespondsJson;

    /** GET /api/admin/detail/{detail}/laporan-kondisi */
    public function index(BookingDetail $detail)
    {
        return $this->ok(LaporanKondisiResource::collection($detail->laporanKondisis()->with('pembuat')->orderBy('jenis')->get()));
    }

    /** POST /api/admin/detail/{detail}/laporan-kondisi (multipart: jenis 1|2, kondisi 1|2|3, catatan?, foto?) */
    public function store(Request $request, BookingDetail $detail, LaporanKondisiService $service)
    {
        $data = $request->validate([
            'jenis'   => 'required|in:1,2',
            'kondisi' => 'required|in:1,2,3',
            'catatan' => 'nullable|string|max:500',
            'foto'    => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $laporan = $service->simpan($detail, $request->user(), $data, $request->file('foto'));
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(new LaporanKondisiResource($laporan->load('pembuat')), 'Laporan kondisi tersimpan.', 201);
    }
}
