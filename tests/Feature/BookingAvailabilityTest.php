<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Mobil;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function book(User $u, Mobil $m, int $from, int $to): Booking
    {
        return app(BookingService::class)->create($u->id, [
            'asal_kota' => 1, 'jaminan' => 2,
            'mobils' => [['mobil_id' => $m->id, 'tanggal_sewa' => now()->addDays($from), 'tanggal_kembali' => now()->addDays($to), 'pakai_supir' => 0]],
        ]);
    }

    public function test_overlap_is_rejected_and_cancel_frees_the_car(): void
    {
        $u = User::factory()->create(['role' => 4]);
        $m = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED]);

        $first = $this->book($u, $m, 10, 12);
        $this->book($u, $m, 20, 21); // tidak bentrok → boleh, walau mobil sudah dibooking di tanggal lain

        try {
            $this->book($u, $m, 11, 13); // bentrok sebagian
            $this->fail('Booking bentrok seharusnya ditolak');
        } catch (\DomainException) {
            $this->assertTrue(true);
        }

        $first->update(['status' => Booking::STATUS_CANCELED]);
        $this->assertNotNull($this->book($u, $m, 11, 13)); // setelah dibatalkan, tanggal kembali bebas
        $this->assertSame(Mobil::STATUS_TERSEDIA, $m->fresh()->status); // status global tidak lagi berubah
    }
}
