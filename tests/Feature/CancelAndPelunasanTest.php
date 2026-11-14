<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Supir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CancelAndPelunasanTest extends TestCase
{
    use RefreshDatabase;

    private function booking(?User $customer = null, int $dp = 0, int $status = Booking::STATUS_BOOKED, ?Supir $supir = null, bool $overdue = false): BookingDetail
    {
        $customer ??= User::factory()->create(['role' => 4]);
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED]);
        $booking = Booking::create(['user_id' => $customer->id, 'tanggal_booking' => now()->subDay(), 'asal_kota' => 1,
            'jaminan' => 1, 'status' => $status, 'total_harga' => 200000, 'uang_muka' => 100000]);
        Pembayaran::create(['booking_id' => $booking->id, 'jenis' => 1, 'jumlah' => 100000, 'status_pembayaran' => $dp,
            'jatuh_tempo' => $overdue ? now()->subHour() : now()->addDays(2)]);

        return BookingDetail::create(['booking_id' => $booking->id, 'mobil_id' => $mobil->id, 'supir_id' => $supir?->id,
            'pakai_supir' => $supir ? 1 : 0, 'tanggal_sewa' => now()->addDays(3), 'tanggal_kembali' => now()->addDays(4),
            'lama_sewa' => 1, 'status' => $status]);
    }

    private function pelunasan(Booking $b, int $status = 0): Pembayaran
    {
        return Pembayaran::create(['booking_id' => $b->id, 'jenis' => 2, 'jumlah' => 100000,
            'status_pembayaran' => $status, 'jatuh_tempo' => now()->addDay()]);
    }

    // ───────── #49 pembatalan ─────────

    public function test_customer_cancels_own_booking_only_while_dp_unpaid_and_resources_are_released(): void
    {
        $user  = User::factory()->create(['role' => 4]);
        $supirUser = User::factory()->create(['role' => 3]);
        $supir = Supir::create(['user_id' => $supirUser->id, 'status' => 2, 'status_approval' => 1]);
        $detail = $this->booking($user, supir: $supir);
        $offer  = JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $supir->id, 'status' => 0]);

        Sanctum::actingAs(User::factory()->create(['role' => 4]));
        $this->postJson("/api/bookings/{$detail->booking_id}/cancel")->assertNotFound(); // milik orang lain

        Sanctum::actingAs($user);
        $this->postJson("/api/bookings/{$detail->booking_id}/cancel")->assertOk()->assertJson(['status' => true]);

        $this->assertSame(Booking::STATUS_CANCELED, $detail->booking->fresh()->status);
        $this->assertSame(0, (int) $detail->fresh()->status);
        $this->assertSame(2, (int) $offer->fresh()->status);          // tawaran job ditutup
        $this->assertSame(1, (int) $supir->fresh()->status);          // supir bebas

        $this->postJson("/api/bookings/{$detail->booking_id}/cancel")->assertStatus(422); // sudah batal
    }

    public function test_customer_cannot_cancel_after_dp_verified_but_admin_can(): void
    {
        $user   = User::factory()->create(['role' => 4]);
        $detail = $this->booking($user, dp: 1);

        Sanctum::actingAs($user);
        $this->postJson("/api/bookings/{$detail->booking_id}/cancel")->assertStatus(422);
        $this->assertSame(Booking::STATUS_BOOKED, $detail->booking->fresh()->status);

        $this->actingAs(User::factory()->create(['role' => 2]))
            ->post(route('admin.booking.cancel', $detail->booking_id), ['catatan' => 'customer minta batal'])
            ->assertSessionHas('success');
        $this->assertSame(Booking::STATUS_CANCELED, $detail->booking->fresh()->status);
    }

    public function test_ongoing_booking_cannot_be_canceled(): void
    {
        $detail = $this->booking(dp: 1, status: Booking::STATUS_ONGOING);
        $this->actingAs(User::factory()->create(['role' => 2]))
            ->post(route('admin.booking.cancel', $detail->booking_id))->assertSessionHas('error');
        $this->assertSame(Booking::STATUS_ONGOING, $detail->booking->fresh()->status);
    }

    public function test_scheduler_cancels_only_overdue_unpaid_bookings(): void
    {
        $overdue = $this->booking(overdue: true);
        $paid    = $this->booking(dp: 1, overdue: true);
        $fresh   = $this->booking();

        Artisan::call('booking:check-dp');

        $this->assertSame(Booking::STATUS_CANCELED, $overdue->booking->fresh()->status);
        $this->assertSame(Booking::STATUS_BOOKED, $paid->booking->fresh()->status);
        $this->assertSame(Booking::STATUS_BOOKED, $fresh->booking->fresh()->status);
    }

    // ───────── #48 pelunasan ─────────

    public function test_admin_can_mark_cash_payments_paid_and_pelunasan_needs_dp_first(): void
    {
        $detail = $this->booking();                   // DP belum dibayar
        $b      = $detail->booking;
        $dp     = $b->pembayaranDp;
        $admin  = $this->actingAs(User::factory()->create(['role' => 2]));

        // pelunasan sebelum DP lunas → ditolak
        $pel = $this->pelunasan($b);
        $admin->put(route('admin.pembayaran.lunas', $pel), ['metode_pembayaran' => 1])->assertSessionHas('error');
        $this->assertSame(0, (int) $pel->fresh()->status_pembayaran);

        // DP tunai → lunas (pelunasan yang sudah ada tidak diduplikasi)
        $admin->put(route('admin.pembayaran.lunas', $dp), ['metode_pembayaran' => 1]);
        $this->assertSame(1, (int) $dp->fresh()->status_pembayaran);
        $this->assertSame(1, (int) $dp->fresh()->metode_pembayaran);
        $this->assertNotNull($dp->fresh()->tanggal_pembayaran);
        $this->assertSame(1, Pembayaran::where('booking_id', $b->id)->where('jenis', 2)->count());

        // pelunasan tunai → lunas; diulang → ditolak
        $admin->put(route('admin.pembayaran.lunas', $pel), ['metode_pembayaran' => 1]);
        $this->assertSame(1, (int) $pel->fresh()->status_pembayaran);
        $admin->put(route('admin.pembayaran.lunas', $pel), ['metode_pembayaran' => 1])->assertSessionHas('error');
    }

    public function test_cannot_mark_paid_for_canceled_booking(): void
    {
        $detail = $this->booking(status: Booking::STATUS_CANCELED);
        $this->actingAs(User::factory()->create(['role' => 2]))
            ->put(route('admin.pembayaran.lunas', $detail->booking->pembayaranDp), ['metode_pembayaran' => 1])
            ->assertSessionHas('error');
        $this->assertSame(0, (int) $detail->booking->pembayaranDp->fresh()->status_pembayaran);
    }

    public function test_customer_uploads_pelunasan_proof_via_api_after_dp_paid(): void
    {
        Storage::fake('public');
        $user   = User::factory()->create(['role' => 4]);
        $detail = $this->booking($user, dp: 1, status: Booking::STATUS_DONE);
        $pel    = $this->pelunasan($detail->booking);
        $file   = fn () => UploadedFile::fake()->image('bukti.png');
        Sanctum::actingAs($user);

        // pembayaran milik booking lain → tidak ditemukan
        $otherPel = $this->pelunasan($this->booking(dp: 1, status: Booking::STATUS_DONE)->booking);
        $this->post("/api/bookings/{$detail->booking_id}/pembayaran/upload",
            ['bukti_bayar' => $file(), 'pembayaran_id' => $otherPel->id], ['Accept' => 'application/json'])->assertNotFound();

        // pelunasan sendiri, booking sudah selesai & jatuh tempo tidak membatasi
        $this->post("/api/bookings/{$detail->booking_id}/pembayaran/upload",
            ['bukti_bayar' => $file(), 'pembayaran_id' => $pel->id, 'metode_pembayaran' => 2], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(2, (int) $pel->fresh()->status_pembayaran);
        $this->assertNotNull($pel->fresh()->foto_bukti);

        // booking selesai dengan pelunasan belum lunas tetap muncul di daftar aktif; setelah lunas hilang
        $this->getJson('/api/bookings')->assertJsonFragment(['id' => $detail->booking_id])
            ->assertJsonPath('data.0.pelunasan.status_pembayaran', 2);
        $pel->update(['status_pembayaran' => 1]);
        $this->getJson('/api/bookings')->assertJsonMissing(['id' => $detail->booking_id]);
    }

    public function test_pelunasan_upload_rejected_while_dp_not_verified(): void
    {
        $user   = User::factory()->create(['role' => 4]);
        $detail = $this->booking($user, dp: 0);
        $pel    = $this->pelunasan($detail->booking);
        Sanctum::actingAs($user);

        $this->post("/api/bookings/{$detail->booking_id}/pembayaran/upload",
            ['bukti_bayar' => UploadedFile::fake()->image('b.png'), 'pembayaran_id' => $pel->id], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_pages_show_the_new_buttons(): void
    {
        $user   = User::factory()->create(['role' => 4]);
        $detail = $this->booking($user);                       // booked, DP belum dibayar
        $this->pelunasan($detail->booking);
        $admin  = User::factory()->create(['role' => 2]);

        // admin: daftar booking → tombol batal + tombol jemput (tidak lagi bergantung status mobil "dibooking")
        $this->actingAs($admin)->get(route('admin.booking.index'))->assertOk()
            ->assertSee('Batalkan Booking')->assertSee('Konfirmasi Jemput');

        // admin: daftar pembayaran → "Tandai Lunas" untuk yang belum dibayar
        $this->actingAs($admin)->get(route('admin.pembayaran.index'))->assertOk()->assertSee('Tandai lunas');

        // customer web: tombol batal selama DP belum lunas, hilang setelah DP lunas
        $this->actingAs($user)->get(route('booking.index'))->assertOk()->assertSee('Batalkan booking');
        $detail->booking->pembayaranDp->update(['status_pembayaran' => 1]);
        $this->actingAs($user)->get(route('booking.index'))->assertOk()->assertDontSee('Batalkan booking');
    }
}
