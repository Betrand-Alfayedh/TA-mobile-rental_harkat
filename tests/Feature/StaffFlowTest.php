<?php

namespace Tests\Feature;

use App\Models\Approval;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use App\Models\Supir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User      { return User::factory()->create(['role' => 2]); }
    private function superadmin(): User { return User::factory()->create(['role' => 1]); }

    private function supir(int $status = 1): Supir
    {
        $u = User::factory()->create(['role' => 3]);
        return Supir::create(['user_id' => $u->id, 'status' => $status, 'status_approval' => 1]);
    }

    /** Booking 1 unit yang jadwalnya sudah lewat (siap dijemput/dikembalikan). DP dibayar sesuai $dpStatus. */
    private function booking(int $dpStatus = 1, ?Supir $supir = null): BookingDetail
    {
        $customer = User::factory()->create(['role' => 4]);
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED]);
        $booking = Booking::create(['user_id' => $customer->id, 'tanggal_booking' => now()->subDays(3), 'asal_kota' => 1,
            'jaminan' => 1, 'status' => Booking::STATUS_BOOKED, 'total_harga' => 200000, 'uang_muka' => 100000]);
        Pembayaran::create(['booking_id' => $booking->id, 'jenis' => 1, 'jumlah' => 100000,
            'status_pembayaran' => $dpStatus, 'jatuh_tempo' => now()->subDay()]);
        Pembayaran::create(['booking_id' => $booking->id, 'jenis' => 2, 'jumlah' => 100000,
            'status_pembayaran' => 0, 'jatuh_tempo' => now()]);

        return BookingDetail::create(['booking_id' => $booking->id, 'mobil_id' => $mobil->id, 'supir_id' => $supir?->id,
            'pakai_supir' => $supir ? 1 : 0, 'tanggal_sewa' => now()->subDays(2), 'tanggal_kembali' => now()->subDay(),
            'lama_sewa' => 1, 'status' => Booking::STATUS_BOOKED]);
    }

    public function test_pickup_requires_verified_dp_then_pengembalian_charges_late_fee_and_frees_supir(): void
    {
        $supir  = $this->supir(2);
        $detail = $this->booking(dpStatus: 0, supir: $supir);
        $admin  = $this->admin();

        // DP belum diverifikasi → ditolak
        $this->actingAs($admin)->patch(route('admin.booking.konfirmasiJemput', $detail))->assertSessionHas('error');
        $this->assertSame(1, (int) $detail->fresh()->status);

        // DP terverifikasi → dijemput; detail, booking, mobil ikut berubah
        Pembayaran::where('booking_id', $detail->booking_id)->where('jenis', 1)->update(['status_pembayaran' => 1]);
        $this->actingAs($admin)->patch(route('admin.booking.konfirmasiJemput', $detail));
        $this->assertSame(2, (int) $detail->fresh()->status);
        $this->assertSame(Booking::STATUS_ONGOING, $detail->booking->fresh()->status);
        $this->assertSame(Mobil::STATUS_DISEWA, $detail->mobil->fresh()->status);

        // dijemput dua kali → ditolak
        $this->actingAs($admin)->patch(route('admin.booking.konfirmasiJemput', $detail))->assertSessionHas('error');

        // Pengembalian terlambat 5,5 jam → denda 6 jam; jam tidak hilang
        $telat = now()->subDay()->addMinutes(330)->format('Y-m-d H:i:s');
        $payload = ['booking_detail_id' => $detail->id, 'tanggal_kembali_aktual' => $telat, 'kondisi' => 0];
        $this->actingAs($admin)->post(route('admin.pengembalian.store'), $payload)->assertSessionHas('success');

        $p = Pengembalian::first();
        $this->assertSame(6 * Pengembalian::DENDA_PER_JAM, (int) $p->nominal_denda);
        $this->assertSame(100000 + 6 * Pengembalian::DENDA_PER_JAM,
            (int) Pembayaran::where('booking_id', $detail->booking_id)->where('jenis', 2)->value('jumlah'));
        $this->assertSame(3, (int) $detail->fresh()->status);
        $this->assertSame(Booking::STATUS_DONE, $detail->booking->fresh()->status);
        $this->assertSame(Mobil::STATUS_RUSAK, $detail->mobil->fresh()->status);   // kondisi rusak
        $this->assertSame(1, (int) $supir->fresh()->status);                       // supir bebas lagi

        // pengembalian ganda → ditolak
        $this->actingAs($admin)->post(route('admin.pengembalian.store'), $payload)->assertSessionHas('error');
        $this->assertSame(1, Pengembalian::count());
    }

    public function test_pengembalian_rejected_when_unit_not_picked_up(): void
    {
        $detail = $this->booking();
        $this->actingAs($this->admin())->post(route('admin.pengembalian.store'), [
            'booking_detail_id' => $detail->id, 'tanggal_kembali_aktual' => now()->format('Y-m-d H:i:s'), 'kondisi' => 1,
        ])->assertSessionHas('error');
        $this->assertSame(0, Pengembalian::count());
    }

    public function test_only_one_supir_wins_a_job_and_offers_are_private(): void
    {
        $a = $this->supir(); $b = $this->supir();
        $detail = $this->booking();
        $offerA = JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $a->id, 'status' => 0]);
        $offerB = JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $b->id, 'status' => 0]);

        // B tidak boleh memakai offer milik A
        $this->actingAs($b->user)->post(route('supir.acceptJob'), ['job_offer_id' => $offerA->id])->assertSessionHas('error');
        $this->assertNull($detail->fresh()->supir_id);

        // A menerima → supir A bertugas, offer B ditutup
        $this->actingAs($a->user)->post(route('supir.acceptJob'), ['job_offer_id' => $offerA->id])->assertSessionHas('success');
        $this->assertSame($a->id, (int) $detail->fresh()->supir_id);
        $this->assertSame(2, (int) $a->fresh()->status);
        $this->assertSame(2, (int) $offerB->fresh()->status);

        // B menerima offernya sendiri yang sudah ditutup → ditolak
        $this->actingAs($b->user)->post(route('supir.acceptJob'), ['job_offer_id' => $offerB->id])->assertSessionHas('error');
        $this->assertSame($a->id, (int) $detail->fresh()->supir_id);

        // supir yang sedang bertugas tidak boleh mengganti statusnya
        $this->actingAs($a->user)->post(route('supir.updateStatus'), ['status' => 1])->assertSessionHas('error');
        $this->assertSame(2, (int) $a->fresh()->status);
    }

    public function test_mobil_edit_is_only_a_proposal_until_superadmin_approves(): void
    {
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_DISEWA, 'status_approval' => 1, 'harga_sewa' => 100000]);
        $payload = [
            'master_mobil_id' => $mobil->master_mobil_id, 'plat_nomor' => $mobil->plat_nomor, 'merk' => $mobil->merk,
            'tahun' => $mobil->tahun, 'harga_sewa' => 250000, 'harga_all_in' => 300000,
            'status' => 1, // mencoba menyelundupkan status: harus diabaikan
        ];

        $this->actingAs($this->admin())->put(route('admin.mobil.update', $mobil), $payload);

        // data live tidak berubah, mobil tetap tampil & statusnya tidak bisa diubah lewat form
        $fresh = $mobil->fresh();
        $this->assertSame(100000, $fresh->harga_sewa);
        $this->assertSame(1, $fresh->status_approval);
        $this->assertSame(Mobil::STATUS_DISEWA, $fresh->status);
        $approval = Approval::first();
        $this->assertSame(2, (int) $approval->status);
        $this->assertArrayNotHasKey('status', $approval->new_data);

        // approve → data baru berlaku; approve lagi → ditolak
        $sa = $this->superadmin();
        $this->actingAs($sa)->post(route('superadmin.approvals.approve', $approval->id))->assertSessionHas('success');
        $this->assertSame(250000, $mobil->fresh()->harga_sewa);
        $this->actingAs($sa)->post(route('superadmin.approvals.approve', $approval->id))->assertSessionHas('error');
        $this->actingAs($sa)->post(route('superadmin.approvals.reject', $approval->id))->assertSessionHas('error');
        $this->assertSame(250000, $mobil->fresh()->harga_sewa);

        // usulan kedua ditolak → data tetap
        $this->actingAs($this->admin())->put(route('admin.mobil.update', $mobil), $payload + ['harga_sewa' => 999000]);
        $second = Approval::latest('id')->first();
        $this->actingAs($sa)->post(route('superadmin.approvals.reject', $second->id))->assertSessionHas('success');
        $this->assertSame(250000, $mobil->fresh()->harga_sewa);
        $this->assertSame(1, $mobil->fresh()->status_approval);
    }

    public function test_supir_edit_is_applied_on_approve_and_password_hash_is_never_shown(): void
    {
        $supir = $this->supir();
        $payload = ['name' => 'Nama Baru', 'email' => $supir->user->email, 'password' => 'rahasia123',
            'no_hp' => '0899', 'alamat' => 'Jl. Baru', 'status' => 1];

        $this->actingAs($this->admin())->put(route('admin.supir.update', $supir), $payload);
        $this->assertNotSame('Nama Baru', $supir->user->fresh()->name); // belum berlaku

        $approval = Approval::first();
        $sa = $this->superadmin();
        $hash = $approval->new_data['password'];
        $this->actingAs($sa)->get(route('superadmin.approvals.index'))->assertOk()->assertDontSee($hash);

        $this->actingAs($sa)->post(route('superadmin.approvals.approve', $approval->id));
        $u = $supir->user->fresh();
        $this->assertSame('Nama Baru', $u->name);
        $this->assertSame('0899', $u->no_hp);
        $this->assertTrue(Hash::check('rahasia123', $u->password));
    }

    public function test_supir_dashboard_lists_open_offers_and_lets_the_supir_accept_without_realtime(): void
    {
        $a = $this->supir(); $b = $this->supir();
        $detail = $this->booking();
        $nama = $detail->mobil->masterMobil->nama;
        $offerA = JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $a->id, 'status' => 0]);
        JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $b->id, 'status' => 0]);

        // tawaran tampil beserta tombol terima; tidak ada galat dari kolom lama (nama_mobil / is_available)
        $this->actingAs($a->user)->get(route('supir.dashboard'))->assertOk()
            ->assertSee('Tawaran job')->assertSee($nama)->assertSee('Terima job')
            ->assertSee('name="job_offer_id" value="' . $offerA->id . '"', false);

        // supir lain tidak melihat milik supir A
        $this->actingAs($a->user)->post(route('supir.acceptJob'), ['job_offer_id' => $offerA->id])->assertSessionHas('success');

        // sesudah diterima: tawaran hilang, job muncul di "Job aktif"; supir B tidak lagi melihat tawaran yang sudah diambil
        $this->actingAs($a->user)->get(route('supir.dashboard'))->assertOk()->assertSee('Belum ada tawaran job')->assertSee($nama)
            ->assertSee('Anda sedang bertugas');
        $this->actingAs($b->user)->get(route('supir.dashboard'))->assertOk()->assertSee('Belum ada tawaran job');
    }

    public function test_supir_who_is_off_duty_sees_offers_but_cannot_accept(): void
    {
        $supir = $this->supir(0); // tidak tersedia
        $detail = $this->booking();
        JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $supir->id, 'status' => 0]);

        $this->actingAs($supir->user)->get(route('supir.dashboard'))->assertOk()
            ->assertSee('tidak tersedia')->assertSee('disabled', false);
    }

    public function test_cancelled_booking_offers_are_not_shown(): void
    {
        $supir = $this->supir();
        $detail = $this->booking();
        JobOffer::create(['booking_detail_id' => $detail->id, 'supir_id' => $supir->id, 'status' => 0]);
        $detail->update(['status' => 0]); // booking dibatalkan

        $this->actingAs($supir->user)->get(route('supir.dashboard'))->assertOk()->assertSee('Belum ada tawaran job');
    }
}
