<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsJson;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminBookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\Request;

/** Pesanan masuk untuk admin. */
class BookingApiController extends Controller
{
    use RespondsJson;

    private const RELASI = ['user', 'pembayarans', 'details.mobil.masterMobil.tipe', 'details.supir.user',
        'details.jobOffers', 'details.pengembalian', 'details.laporanKondisis'];

    /** GET /api/admin/bookings?status=&q=&dari=&sampai=&page=&per_page= */
    public function index(Request $request)
    {
        $request->validate([
            'status'   => 'nullable|in:0,1,2,3',
            'q'        => 'nullable|string|max:100',
            'dari'     => 'nullable|date',
            'sampai'   => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $bookings = Booking::with(self::RELASI)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = $request->q;
                $q->where(fn ($w) => $w->where('id', ltrim($kata, '#'))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$kata}%")->orWhere('no_hp', 'like', "%{$kata}%")));
            })
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('tanggal_booking', '>=', $request->dari))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('tanggal_booking', '<=', $request->sampai))
            ->latest()
            ->paginate((int) $request->input('per_page', 15));

        return $this->page($bookings, AdminBookingResource::class);
    }

    /** GET /api/admin/bookings/{id} */
    public function show(int $id)
    {
        return $this->ok(new AdminBookingResource(Booking::with(self::RELASI)->findOrFail($id)));
    }

    /** POST /api/admin/bookings/{id}/cancel  { catatan? } */
    public function cancel(Request $request, int $id, BookingService $service)
    {
        $request->validate(['catatan' => 'nullable|string|max:200']);
        $booking = Booking::findOrFail($id);

        try {
            $service->cancel($booking, 'Dibatalkan oleh admin.' . ($request->filled('catatan') ? ' ' . $request->catatan : ''));
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        }

        return $this->ok(new AdminBookingResource($booking->fresh(self::RELASI)), 'Booking dibatalkan.');
    }
}
