<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use App\Models\Supir;
use App\Models\User;
use App\Services\PenugasanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Fase 2: alur inti pesanan lewat API admin (Booked → Ongoing → Done). */
class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { return User::factory()->create(['role' => 2]); }

    private function sopir(): Supir
    {
        $u = User::factory()->create(['role' => 3]);
        return Supir::create(['user_id' => $u->id, 'status' => 1, 'status_approval' => 1]);
    }

    private function pelanggan(): User
    {
        return User::factory()->create(['role' => 4, 'no_hp' => '0812', 'alamat' => 'Jl. A', 'asal_kota' => 'Yogyakarta']);
    }

    /** Pelanggan memesan lewat API, hasilnya booking baru. */
    private function pesan(User $cust, int $pakaiSupir = 0): array
    {
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED,
            'harga_sewa' => 500000, 'harga_all_in' => 800000]);
        Sanctum::actingAs($cust);
        $res = $this->postJson('/api/bookings', [
            'asal_kota' => 1, 'jaminan' => 1,
            'mobils' => [[
                'mobil_id' => $mobil->id, 'pakai_supir' => $pakaiSupir,
                'tanggal_sewa' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'tanggal_kembali' => now()->addDays(4)->format('Y-m-d H:i:s'),
            ]],
        ])->assertCreated();

        return [$res->json('data.id'), $mobil];
    }

    private function unggahDp(User $cust, int $bookingId): void
    {
        Sanctum::actingAs($cust);
        $this->post("/api/bookings/{$bookingId}/pembayaran/upload",
            ['bukti_bayar' => UploadedFile::fake()->image('dp.png'), 'metode_pembayaran' => 2], ['Accept' => 'application/json'])->assertOk();
    }

    // ───────── alur penuh ─────────

    public function test_full_flow_customer_orders_admin_verifies_assigns_pickup_and_return_with_late_fee(): void
    {
        Storage::fake('public');
        $cust = $this->pelanggan();
        $sopir = $this->sopir();
        $admin = $this->admin();

        [$bookingId] = $this->pesan($cust, pakaiSupir: 1);
        $this->unggahDp($cust, $bookingId);

        Sanctum::actingAs($admin);

        // pesanan masuk dan DP menunggu verifikasi
        $this->getJson('/api/admin/bookings?status=1')->assertOk()
            ->assertJsonPath('data.0.id', $bookingId)->assertJsonPath('data.0.penyewa.name', $cust->name)
            ->assertJsonPath('meta.total', 1);
        $dp = Pembayaran::where('booking_id', $bookingId)->where('jenis', 1)->first();
        $this->getJson('/api/admin/pembayaran?status=2')->assertJsonPath('data.0.id', $dp->id);

        // jemput sebelum DP diverifikasi → ditolak
        $detail = BookingDetail::where('booking_id', $bookingId)->first();
        $this->patchJson("/api/admin/detail/{$detail->id}/jemput")->assertStatus(422)
            ->assertJsonPath('message', 'DP belum diverifikasi. Verifikasi pembayaran DP terlebih dahulu.');

        // verifikasi DP → lunas, tagihan pelunasan terbentuk, invoice terkirim
        $this->postJson("/api/admin/pembayaran/{$dp->id}/verifikasi")->assertOk()
            ->assertJsonPath('data.pembayaran.status_pembayaran', 1)->assertJsonPath('data.invoice_terkirim', true);
        $this->assertSame($admin->id, (int) $dp->fresh()->dicatat_oleh);
        $this->assertTrue(Pembayaran::where('booking_id', $bookingId)->where('jenis', 2)->exists());

        // penugasan → sopir menerima
        $this->postJson("/api/admin/detail/{$detail->id}/penugasan")->assertOk()->assertJsonPath('data.jumlah_sopir', 1)
            ->assertJsonPath('data.detail.menunggu_supir', true);
        app(PenugasanService::class)->terima(JobOffer::where('supir_id', $sopir->id)->value('id'), $sopir);

        // jemput → Ongoing
        $this->patchJson("/api/admin/detail/{$detail->id}/jemput")->assertOk()->assertJsonPath('data.status', Booking::STATUS_ONGOING);
        $this->assertSame(Booking::STATUS_ONGOING, Booking::find($bookingId)->status);

        // kembali terlambat 90 menit → denda 2 jam, booking Done, sopir bebas, mobil maintenance
        $telat = Carbon::parse($detail->fresh()->tanggal_kembali)->addMinutes(90)->format('Y-m-d H:i:s');
        $this->post("/api/admin/detail/{$detail->id}/pengembalian", [
            'tanggal_kembali_aktual' => $telat, 'kondisi' => 1, 'gambar' => UploadedFile::fake()->image('kembali.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated()
            ->assertJsonPath('data.jam_terlambat', 2)
            ->assertJsonPath('data.nominal_denda', 2 * Pengembalian::DENDA_PER_JAM)
            ->assertJsonPath('data.status_pesanan', 'Done');

        $this->assertSame(Booking::STATUS_DONE, Booking::find($bookingId)->status);
        $this->assertSame(1, (int) $sopir->fresh()->status);
        $this->assertSame(Mobil::STATUS_MAINTENANCE, $detail->mobil->fresh()->status);

        // denda masuk tagihan pelunasan; admin mencatat tunai
        $pel = Pembayaran::where('booking_id', $bookingId)->where('jenis', 2)->first();
        $this->assertGreaterThanOrEqual(2 * Pengembalian::DENDA_PER_JAM, (int) $pel->jumlah);
        $this->postJson("/api/admin/pembayaran/{$pel->id}/lunas", ['metode_pembayaran' => 1])->assertOk()
            ->assertJsonPath('data.pembayaran.status_pembayaran', 1);

        // kembali dua kali → ditolak
        $this->post("/api/admin/detail/{$detail->id}/pengembalian", ['tanggal_kembali_aktual' => $telat, 'kondisi' => 1],
            ['Accept' => 'application/json'])->assertStatus(422);
    }

    // ───────── pembayaran ─────────

    public function test_tolak_resets_payment_and_requires_waiting_status(): void
    {
        Storage::fake('public');
        $cust = $this->pelanggan();
        [$bookingId] = $this->pesan($cust);
        $this->unggahDp($cust, $bookingId);
        $dp = Pembayaran::where('booking_id', $bookingId)->where('jenis', 1)->first();

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/pembayaran/{$dp->id}/tolak", ['catatan_admin' => 'Nominal kurang'])->assertOk();
        $this->assertSame(0, (int) $dp->fresh()->status_pembayaran);
        $this->assertNull($dp->fresh()->foto_bukti);
        $this->assertSame('Nominal kurang', $dp->fresh()->catatan_admin);

        // sudah tidak menunggu verifikasi → tidak bisa diverifikasi atau ditolak lagi
        $this->postJson("/api/admin/pembayaran/{$dp->id}/verifikasi")->assertStatus(422);
        $this->postJson("/api/admin/pembayaran/{$dp->id}/tolak")->assertStatus(422);
    }

    public function test_cash_payment_validates_method_and_pelunasan_needs_dp_first(): void
    {
        $cust = $this->pelanggan();
        [$bookingId] = $this->pesan($cust);
        $dp = Pembayaran::where('booking_id', $bookingId)->where('jenis', 1)->first();
        $pelunasan = Pembayaran::create(['booking_id' => $bookingId, 'jenis' => 2, 'jumlah' => 1, 'status_pembayaran' => 0, 'jatuh_tempo' => now()->addDays(3)]);

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/pembayaran/{$dp->id}/lunas", ['metode_pembayaran' => 9])->assertStatus(422);
        $this->postJson("/api/admin/pembayaran/{$pelunasan->id}/lunas", ['metode_pembayaran' => 1])->assertStatus(422)
            ->assertJsonPath('message', 'Selesaikan pembayaran DP terlebih dahulu.');
        $this->postJson("/api/admin/pembayaran/{$dp->id}/lunas", ['metode_pembayaran' => 1])->assertOk();
        $this->postJson("/api/admin/pembayaran/{$pelunasan->id}/lunas", ['metode_pembayaran' => 1])->assertOk();
    }

    // ───────── pesanan ─────────

    public function test_admin_can_cancel_a_booked_order_but_not_twice(): void
    {
        [$bookingId, $mobil] = $this->pesan($this->pelanggan());

        Sanctum::actingAs($this->admin());
        $this->postJson("/api/admin/bookings/{$bookingId}/cancel", ['catatan' => 'Pelanggan batal'])->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_CANCELED);
        $this->postJson("/api/admin/bookings/{$bookingId}/cancel")->assertStatus(422);
        $this->getJson('/api/admin/bookings/999999')->assertNotFound();
    }

    public function test_booking_list_filters_by_status_and_search_and_paginates(): void
    {
        $budi = User::factory()->create(['role' => 4, 'name' => 'Budi Santoso', 'no_hp' => '0811', 'alamat' => 'x', 'asal_kota' => 'x']);
        $sari = User::factory()->create(['role' => 4, 'name' => 'Sari Wulan', 'no_hp' => '0822', 'alamat' => 'x', 'asal_kota' => 'x']);
        [$b1] = $this->pesan($budi);
        [$b2] = $this->pesan($sari);
        Booking::find($b2)->update(['status' => Booking::STATUS_DONE]);

        Sanctum::actingAs($this->admin());
        $this->getJson('/api/admin/bookings?q=Budi')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $b1);
        $this->getJson('/api/admin/bookings?q=0822')->assertJsonPath('data.0.id', $b2);
        $this->getJson("/api/admin/bookings?q=%23{$b1}")->assertJsonPath('data.0.id', $b1);
        $this->getJson('/api/admin/bookings?status=3')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $b2);
        $this->getJson('/api/admin/bookings?per_page=1')->assertJsonPath('meta.last_page', 2)->assertJsonCount(1, 'data');
        $this->getJson('/api/admin/bookings?status=9')->assertStatus(422);
    }

    public function test_dashboard_counts_what_needs_action(): void
    {
        Storage::fake('public');
        $cust = $this->pelanggan();
        [$b1] = $this->pesan($cust, pakaiSupir: 1);
        $this->unggahDp($cust, $b1);
        $this->sopir();

        Sanctum::actingAs($this->admin());
        $d = $this->getJson('/api/admin/dashboard')->assertOk();
        $d->assertJsonPath('data.pesanan.booked', 1)
            ->assertJsonPath('data.pesanan.masuk_hari_ini', 1)
            ->assertJsonPath('data.perlu_tindakan.pembayaran_menunggu_verifikasi', 1)
            ->assertJsonPath('data.perlu_tindakan.perlu_penugasan_sopir', 1)
            ->assertJsonPath('data.sopir.tersedia', 1);

        // setelah ditawarkan, tidak lagi dihitung "perlu penugasan"
        $detail = BookingDetail::where('booking_id', $b1)->first();
        $this->postJson("/api/admin/detail/{$detail->id}/penugasan")->assertOk();
        $this->getJson('/api/admin/dashboard')->assertJsonPath('data.perlu_tindakan.perlu_penugasan_sopir', 0);
    }

    public function test_penugasan_rejected_for_self_drive_unit_and_when_no_driver(): void
    {
        [$b1] = $this->pesan($this->pelanggan(), pakaiSupir: 0);
        [$b2] = $this->pesan($this->pelanggan(), pakaiSupir: 1);

        Sanctum::actingAs($this->admin());
        $this->postJson('/api/admin/detail/' . BookingDetail::where('booking_id', $b1)->value('id') . '/penugasan')
            ->assertStatus(422)->assertJsonPath('message', 'Unit ini disewa tanpa sopir.');
        $this->postJson('/api/admin/detail/' . BookingDetail::where('booking_id', $b2)->value('id') . '/penugasan')
            ->assertStatus(422)->assertJsonPath('message', 'Tidak ada supir yang available.');
    }

    // ───────── pembatas peran ─────────

    public function test_every_admin_endpoint_is_closed_to_customer_and_driver_and_guest(): void
    {
        $detail = BookingDetail::create(['booking_id' => Booking::create(['user_id' => $this->pelanggan()->id, 'tanggal_booking' => now(), 'asal_kota' => 1,
            'jaminan' => 1, 'status' => 1, 'total_harga' => 1, 'uang_muka' => 1])->id,
            'mobil_id' => Mobil::factory()->create()->id, 'pakai_supir' => 0, 'tanggal_sewa' => now(), 'tanggal_kembali' => now()->addDay(), 'lama_sewa' => 1, 'status' => 1]);

        $endpoints = [
            ['GET', '/api/admin/dashboard'], ['GET', '/api/admin/bookings'], ['GET', '/api/admin/bookings/1'],
            ['POST', '/api/admin/bookings/1/cancel'], ['GET', '/api/admin/pembayaran'],
            ['POST', '/api/admin/pembayaran/1/verifikasi'], ['POST', '/api/admin/pembayaran/1/tolak'], ['POST', '/api/admin/pembayaran/1/lunas'],
            ['POST', "/api/admin/detail/{$detail->id}/penugasan"], ['PATCH', "/api/admin/detail/{$detail->id}/jemput"],
            ['POST', "/api/admin/detail/{$detail->id}/pengembalian"],
        ];

        foreach ($endpoints as [$method, $url]) {
            $this->json($method, $url)->assertUnauthorized();
        }
        foreach ([$this->pelanggan(), $this->sopir()->user] as $bukanAdmin) {
            Sanctum::actingAs($bukanAdmin);
            foreach ($endpoints as [$method, $url]) {
                $this->json($method, $url)->assertForbidden();
            }
        }
    }
}
