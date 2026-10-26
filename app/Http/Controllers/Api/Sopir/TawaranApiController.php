<?php

namespace App\Http\Controllers\Api\Sopir;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\SopirTugasResource;
use App\Http\Resources\TawaranResource;
use App\Models\JobOffer;
use App\Services\PenugasanService;
use Illuminate\Http\Request;

class TawaranApiController extends Controller
{
    use RespondsJson;

    /** GET /api/sopir/tawaran */
    public function index(Request $request)
    {
        $tawaran = DashboardApiController::tawaranTerbuka($request->user()->supir->id)
            ->with(['booking_detail.mobil.masterMobil', 'booking_detail.booking.user'])
            ->latest()->get();

        return $this->ok(TawaranResource::collection($tawaran));
    }

    /** POST /api/sopir/tawaran/{id}/terima */
    public function terima(Request $request, int $id, PenugasanService $service)
    {
        $supir = $request->user()->supir;

        try {
            $service->terima($id, $supir);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), $e->getCode() === 409 ? 409 : 422);
        }

        $detail = JobOffer::findOrFail($id)->booking_detail()->with(['mobil.masterMobil', 'booking.user'])->first();

        return $this->ok(new SopirTugasResource($detail), 'Tugas diterima.');
    }

    /** POST /api/sopir/tawaran/{id}/tolak */
    public function tolak(Request $request, int $id, PenugasanService $service)
    {
        try {
            $service->tolak($id, $request->user()->supir);
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage(), $e->getCode() === 409 ? 409 : 422);
        }

        return $this->ok(null, 'Tawaran ditolak.');
    }
}
