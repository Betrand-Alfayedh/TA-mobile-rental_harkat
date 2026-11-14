<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Supir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Data master tidak dihapus: admin menonaktifkan / mengaktifkan kembali. */
class NonaktifkanTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 2]);
    }

    private function supir(int $status = 1): Supir
    {
        $u = User::factory()->create(['role' => 3]);

        return Supir::create(['user_id' => $u->id, 'status' => $status, 'status_approval' => 1]);
    }

    /** Pesanan satu unit; $status = status unit dan pesanan. */
    private function pesanan(User $pelanggan, Mobil $mobil, int $status = Booking::STATUS_BOOKED, ?Supir $supir = null): BookingDetail
    {
        $booking = Booking::create(['user_id' => $pelanggan->id, 'tanggal_booking' => now(), 'asal_kota' => 1,
            'jaminan' => 1, 'status' => $status, 'total_harga' => 200000, 'uang_muka' => 100000]);

        return BookingDetail::create(['booking_id' => $booking->id, 'mobil_id' => $mobil->id, 'supir_id' => $supir?->id,
            'pakai_supir' => $supir ? 1 : 0, 'tanggal_sewa' => now()->addDays(3), 'tanggal_kembali' => now()->addDays(4),
            'lama_sewa' => 1, 'status' => $status]);
    }

    private function mobil(): Mobil
    {
        return Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED]);
    }

    // ───────── mobil ─────────

    public function test_mobil_can_be_deactivated_and_reactivated_without_deleting_it(): void
    {
        $mobil = $this->mobil();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.mobil.aktif', $mobil))->assertSessionHas('success');
        $this->assertFalse($mobil->fresh()->aktif);
        $this->assertDatabaseHas('mobils', ['id' => $mobil->id]); // barisnya tetap ada

        $this->actingAs($admin)->patch(route('admin.mobil.aktif', $mobil))->assertSessionHas('success');
        $this->assertTrue($mobil->fresh()->aktif);
    }

    public function test_inactive_mobil_cannot_be_booked_and_is_hidden_from_listings(): void
    {
        $mobil = $this->mobil();
        $mobil->update(['aktif' => false]);

        $this->assertFalse($mobil->fresh()->isAvailableFor(now()->addDays(10), now()->addDays(11)));
        $this->assertSame(0, Mobil::freeBetween(now()->addDays(10), now()->addDays(11))->count());
        $this->assertSame(0, Mobil::approved()->count());

        $this->actingAs($this->admin())->get(route('admin.booking.create'))
            ->assertRedirect(); // tidak ada unit aktif: kembali dengan pesan, bukan menampilkan unit nonaktif
    }

    public function test_mobil_with_a_running_booking_cannot_be_deactivated(): void
    {
        $mobil = $this->mobil();
        $this->pesanan(User::factory()->create(['role' => 4]), $mobil);

        $this->actingAs($this->admin())->patch(route('admin.mobil.aktif', $mobil))->assertSessionHas('error');
        $this->assertTrue($mobil->fresh()->aktif);
    }

    public function test_mobil_with_only_finished_bookings_can_be_deactivated(): void
    {
        $mobil = $this->mobil();
        $this->pesanan(User::factory()->create(['role' => 4]), $mobil, Booking::STATUS_DONE);

        $this->actingAs($this->admin())->patch(route('admin.mobil.aktif', $mobil))->assertSessionHas('success');
        $this->assertFalse($mobil->fresh()->aktif);
    }

    // ───────── pelanggan ─────────

    public function test_pelanggan_deactivated_is_hidden_from_new_orders_and_cannot_log_in(): void
    {
        $pelanggan = User::factory()->create(['role' => 4]);
        $this->mobil();

        $this->actingAs($this->admin())->patch(route('admin.pelanggan.aktif', $pelanggan))->assertSessionHas('success');
        $this->assertFalse($pelanggan->fresh()->aktif);

        $this->actingAs($this->admin())->get(route('admin.booking.create'))->assertOk()
            ->assertDontSee($pelanggan->name);

        auth()->logout();
        $this->post('/login', ['email' => $pelanggan->email, 'password' => 'password', 'role' => 'customer'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_pelanggan_with_a_running_booking_cannot_be_deactivated(): void
    {
        $pelanggan = User::factory()->create(['role' => 4]);
        $this->pesanan($pelanggan, $this->mobil(), Booking::STATUS_ONGOING);

        $this->actingAs($this->admin())->patch(route('admin.pelanggan.aktif', $pelanggan))->assertSessionHas('error');
        $this->assertTrue($pelanggan->fresh()->aktif);
    }

    public function test_deactivated_pelanggan_with_an_open_session_is_logged_out(): void
    {
        $pelanggan = User::factory()->create(['role' => 4, 'aktif' => false]);

        $this->actingAs($pelanggan)->get(route('home'))->assertRedirect(route('landing-page'));
        $this->assertGuest();
    }

    // ───────── sopir ─────────

    public function test_supir_deactivation_closes_open_offers_and_revokes_tokens(): void
    {
        $supir = $this->supir();
        $detail = $this->pesanan(User::factory()->create(['role' => 4]), $this->mobil());
        $offer = JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $supir->id, 'status' => 0]);
        $supir->user->createToken('hp');

        $this->actingAs($this->admin())->patch(route('admin.supir.aktif', $supir))->assertSessionHas('success');

        $this->assertFalse($supir->user->fresh()->aktif);
        $this->assertSame(2, (int) $offer->fresh()->status);
        $this->assertSame(0, $supir->user->tokens()->count());
        $this->assertDatabaseHas('supirs', ['id' => $supir->id]);
    }

    public function test_supir_on_duty_cannot_be_deactivated(): void
    {
        $supir = $this->supir(2);

        $this->actingAs($this->admin())->patch(route('admin.supir.aktif', $supir))->assertSessionHas('error');
        $this->assertTrue($supir->user->fresh()->aktif);
    }

    public function test_supir_with_an_assigned_unfinished_task_cannot_be_deactivated(): void
    {
        $supir = $this->supir();
        $this->pesanan(User::factory()->create(['role' => 4]), $this->mobil(), Booking::STATUS_BOOKED, $supir);

        $this->actingAs($this->admin())->patch(route('admin.supir.aktif', $supir))->assertSessionHas('error');
        $this->assertTrue($supir->user->fresh()->aktif);
    }

    // ───────── akses ─────────

    public function test_only_admin_can_toggle(): void
    {
        $mobil = $this->mobil();
        $cust = User::factory()->create(['role' => 4]);

        $this->actingAs($cust)->patch(route('admin.mobil.aktif', $mobil));
        $this->assertTrue($mobil->fresh()->aktif);
    }
}
