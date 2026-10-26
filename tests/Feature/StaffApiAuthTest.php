<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Supir;
use App\Models\User;
use App\Services\PenugasanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Fase 1: login semua peran, pembatas peran API, dan penugasan sopir (terima/tolak). */
class StaffApiAuthTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $u, string $password = 'password')
    {
        return $this->postJson('/api/login', ['email' => $u->email, 'password' => $password]);
    }

    private function sopir(int $status = 1, int $approval = 1): Supir
    {
        $u = User::factory()->create(['role' => 3]);
        return Supir::create(['user_id' => $u->id, 'status' => $status, 'status_approval' => $approval]);
    }

    public function test_admin_sopir_and_customer_can_login_and_get_role(): void
    {
        $admin = User::factory()->create(['role' => 2]);
        $sopir = $this->sopir();
        $cust  = User::factory()->create(['role' => 4]);

        $this->login($admin)->assertOk()->assertJsonPath('data.user.role', 2)->assertJsonStructure(['data' => ['token']]);
        $this->login($sopir->user)->assertOk()->assertJsonPath('data.user.role', 3);
        $this->login($cust)->assertOk()->assertJsonPath('data.user.role', 4);
    }

    public function test_login_rejects_superadmin_inactive_account_unapproved_driver_and_wrong_password(): void
    {
        $this->login(User::factory()->create(['role' => 1]))->assertForbidden();

        $nonaktif = User::factory()->create(['role' => 2, 'aktif' => false]);
        $this->login($nonaktif)->assertForbidden()->assertJsonPath('status', false);

        $this->login($this->sopir(approval: 2)->user)->assertForbidden();

        $this->login(User::factory()->create(['role' => 2]), 'salah-password')->assertStatus(422);
    }

    public function test_token_expires_in_seven_days_and_carries_role_ability(): void
    {
        $admin = User::factory()->create(['role' => 2]);
        $this->login($admin)->assertOk();

        $token = $admin->tokens()->first();
        $this->assertSame(['admin'], $token->abilities);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $token->expires_at->timestamp, 60);
    }

    public function test_api_role_middleware_allows_only_the_listed_role_and_always_returns_json(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'api.role:admin'])->get('/api/_uji/admin', fn () => response()->json(['ok' => true]));

        // tanpa token → 401 JSON (bukan redirect)
        $this->getJson('/api/_uji/admin')->assertUnauthorized();
        $this->get('/api/_uji/admin')->assertUnauthorized();

        $admin = User::factory()->create(['role' => 2]);
        $cust  = User::factory()->create(['role' => 4]);
        $sopir = $this->sopir()->user;

        $this->actingAs($admin, 'sanctum')->getJson('/api/_uji/admin')->assertOk();
        $this->actingAs($cust, 'sanctum')->getJson('/api/_uji/admin')->assertForbidden();
        $this->actingAs($sopir, 'sanctum')->getJson('/api/_uji/admin')->assertForbidden();

        // akun yang dinonaktifkan setelah login langsung kehilangan akses
        $admin->update(['aktif' => false]);
        $this->actingAs($admin->fresh(), 'sanctum')->getJson('/api/_uji/admin')->assertForbidden();
    }

    public function test_customer_booking_endpoints_are_closed_to_staff(): void
    {
        $this->actingAs(User::factory()->create(['role' => 2]), 'sanctum')->getJson('/api/bookings')->assertForbidden();
        $this->actingAs($this->sopir()->user, 'sanctum')->getJson('/api/bookings')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 4]), 'sanctum')->getJson('/api/bookings')->assertOk();
    }

    public function test_register_creates_a_customer_and_returns_a_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Budi', 'email' => 'budi@example.test', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
        ])->assertCreated()->assertJsonPath('data.user.role', 4)->assertJsonStructure(['data' => ['token']]);

        $this->assertTrue((bool) User::where('email', 'budi@example.test')->value('aktif'));
    }

    // ───────── Penugasan sopir ─────────

    private function detailBersopir(): BookingDetail
    {
        $cust = User::factory()->create(['role' => 4]);
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED]);
        $booking = Booking::create(['user_id' => $cust->id, 'tanggal_booking' => now(), 'asal_kota' => 1, 'jaminan' => 1,
            'status' => Booking::STATUS_BOOKED, 'total_harga' => 800000, 'uang_muka' => 400000]);

        return BookingDetail::create(['booking_id' => $booking->id, 'mobil_id' => $mobil->id, 'pakai_supir' => 1,
            'tanggal_sewa' => now()->addDays(3), 'tanggal_kembali' => now()->addDays(5), 'lama_sewa' => 2, 'status' => Booking::STATUS_BOOKED]);
    }

    public function test_tawarkan_only_offers_available_approved_active_drivers_and_keeps_pakai_supir(): void
    {
        $layak     = $this->sopir(1, 1);
        $libur     = $this->sopir(0, 1);
        $bertugas  = $this->sopir(2, 1);
        $belumSetuju = $this->sopir(1, 2);
        $nonaktif  = $this->sopir(1, 1);
        $nonaktif->user->update(['aktif' => false]);
        $detail = $this->detailBersopir();

        $jumlah = app(PenugasanService::class)->tawarkan($detail);

        $this->assertSame(1, $jumlah);
        $this->assertSame([$layak->id], JobOffer::where('booking_detail_id', $detail->id)->pluck('supir_id')->all());
        $this->assertSame(1, (int) $detail->fresh()->pakai_supir, 'pakai_supir tidak boleh ditimpa');
    }

    public function test_tawarkan_rejects_a_unit_rented_without_driver(): void
    {
        $svc = app(PenugasanService::class);

        $tanpa = $this->detailBersopir();
        $tanpa->update(['pakai_supir' => 0]);
        $this->expectExceptionObject(new \DomainException('Unit ini disewa tanpa sopir.'));
        $svc->tawarkan($tanpa);
    }

    public function test_tawarkan_fails_when_no_driver_is_available(): void
    {
        $this->expectExceptionObject(new \DomainException('Tidak ada supir yang available.'));
        app(PenugasanService::class)->tawarkan($this->detailBersopir());
    }

    public function test_driver_can_accept_once_and_the_other_offer_is_closed(): void
    {
        $a = $this->sopir(); $b = $this->sopir();
        $detail = $this->detailBersopir();
        $svc = app(PenugasanService::class);
        $svc->tawarkan($detail);

        $offerA = JobOffer::where('supir_id', $a->id)->first();
        $offerB = JobOffer::where('supir_id', $b->id)->first();

        $svc->terima($offerA->id, $a);

        $this->assertSame($a->id, $detail->fresh()->supir_id);
        $this->assertSame(2, (int) $a->fresh()->status);
        $this->assertSame(PenugasanService::DITUTUP, (int) $offerB->fresh()->status);

        $this->expectException(\DomainException::class);
        $svc->terima($offerB->id, $b);   // sudah diambil sopir lain
    }

    public function test_driver_reject_marks_offer_rejected_and_is_not_reoffered(): void
    {
        $a = $this->sopir(); $b = $this->sopir();
        $detail = $this->detailBersopir();
        $svc = app(PenugasanService::class);
        $svc->tawarkan($detail);

        $svc->tolak(JobOffer::where('supir_id', $a->id)->first()->id, $a);
        $this->assertSame(PenugasanService::DITOLAK, (int) JobOffer::where('supir_id', $a->id)->value('status'));
        $this->assertNull($detail->fresh()->supir_id);

        // tawaran ulang: hanya sopir yang belum menolak
        $this->assertSame(1, $svc->tawarkan($detail->fresh()));
        $this->assertSame(PenugasanService::DITOLAK, (int) JobOffer::where('supir_id', $a->id)->value('status'));

        // tawaran yang sudah ditolak tidak dapat ditolak/diterima lagi
        $this->expectException(\DomainException::class);
        $svc->tolak(JobOffer::where('supir_id', $a->id)->first()->id, $a);
    }

    public function test_driver_cannot_answer_someone_elses_offer(): void
    {
        $a = $this->sopir(); $b = $this->sopir();
        $detail = $this->detailBersopir();
        $svc = app(PenugasanService::class);
        $svc->tawarkan($detail);

        $this->expectException(\DomainException::class);
        $svc->tolak(JobOffer::where('supir_id', $a->id)->first()->id, $b);
    }

    public function test_unapproved_driver_cannot_accept(): void
    {
        $detail = $this->detailBersopir();
        $baru = $this->sopir(1, 2);
        $offer = JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $baru->id, 'status' => 0]);

        $this->expectExceptionObject(new \DomainException('Akun supir belum disetujui.'));
        app(PenugasanService::class)->terima($offer->id, $baru);
    }

    public function test_web_assign_job_offers_drivers_without_overwriting_pakai_supir_and_shows_waiting_state(): void
    {
        $sopir  = $this->sopir();
        $detail = $this->detailBersopir();
        $admin  = User::factory()->create(['role' => 2]);

        $this->actingAs($admin)->post(route('admin.assignJob', $detail))->assertSessionHas('success');

        $this->assertSame(1, (int) $detail->fresh()->pakai_supir);
        $this->actingAs($admin)->get(route('admin.booking.index'))->assertSee('Menunggu supir merespon');

        // setelah sopir menolak, admin bisa menugaskan lagi (tombol muncul kembali)
        app(PenugasanService::class)->tolak(JobOffer::where('supir_id', $sopir->id)->value('id'), $sopir);
        $this->actingAs($admin)->get(route('admin.booking.index'))->assertDontSee('Menunggu supir merespon')->assertSee('Assign Job');

        // tidak ada kandidat lagi → pesan jelas
        $this->actingAs($admin)->post(route('admin.assignJob', $detail))->assertSessionHas('error', 'Tidak ada supir yang available.');
    }
}
