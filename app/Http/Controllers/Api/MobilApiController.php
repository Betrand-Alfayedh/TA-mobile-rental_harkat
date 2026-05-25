<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobilResource;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\TipeMobil;
use Illuminate\Http\Request;

class MobilApiController extends Controller
{
    /**
     * List cars with optional search & type filter.
     *
     * GET /api/mobils
     */
    public function index(Request $request)
    {
        $query = Mobil::with('masterMobil.tipe', 'images')
            ->where('status', '<>', 0)
            ->where('status_approval', 1);

        if ($request->filled('search')) {
            $query->whereHas('masterMobil', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->whereHas('masterMobil.tipe', function ($q) use ($request) {
                $q->where('nama_tipe', $request->type);
            });
        }

        $perPage = $request->input('per_page', 10);
        $mobils  = $query->paginate($perPage);

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => MobilResource::collection($mobils),
            'meta'    => [
                'current_page' => $mobils->currentPage(),
                'last_page'    => $mobils->lastPage(),
                'per_page'     => $mobils->perPage(),
                'total'        => $mobils->total(),
            ],
        ]);
    }

    /**
     * Show single car detail.
     *
     * GET /api/mobils/{id}
     */
    public function show(int $id)
    {
        $mobil = Mobil::with('masterMobil.tipe', 'images')->findOrFail($id);

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => new MobilResource($mobil),
        ]);
    }

    /**
     * Get all car types for filter chips.
     *
     * GET /api/tipe-mobils
     */
    public function tipeIndex()
    {
        $tipes = TipeMobil::all(['id', 'nama_tipe']);

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => $tipes,
        ]);
    }

    /**
     * Check available cars between two dates.
     *
     * GET /api/mobils/available?tanggal_sewa=&tanggal_kembali=
     */
    public function available(Request $request)
    {
        $request->validate([
            'tanggal_sewa'    => 'required|date',
            'tanggal_kembali' => 'required|date|after_or_equal:tanggal_sewa',
        ]);

        $start = $request->tanggal_sewa;
        $end   = $request->tanggal_kembali;

        $bookedIds = BookingDetail::where('tanggal_sewa', '<=', $end)
            ->where('tanggal_kembali', '>=', $start)
            ->whereHas('booking', fn($q) => $q->whereIn('status', [1, 2]))
            ->pluck('mobil_id')
            ->toArray();

        $mobils = Mobil::with('masterMobil.tipe', 'images')
            ->where('status', 1)
            ->where('status_approval', 1)
            ->whereNotIn('id', $bookedIds)
            ->get();

        return response()->json([
            'status'  => true,
            'message' => 'OK',
            'data'    => MobilResource::collection($mobils),
        ]);
    }
}
