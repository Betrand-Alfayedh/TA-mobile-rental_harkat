<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\LaporanKondisi;
use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use App\Models\Supir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Fase 3: armada, sopir, penyewa, laporan kondisi (admin), dan rekap bulanan. */
class AdminMasterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 2]));
    }

    private function mobilPayload(array $o = []): array
    {
        return $o + ['master_mobil_id' => MasterMobil::factory()->create()->id, 'plat_nomor' => 'AB 1234 CD', 'merk' => 'Toyota',
            'tahun' => 2023, 'harga_sewa' => 500000, 'harga_all_in' => 800000];
    }

    private function sewa(Mobil $mobil, int $status, int $supirId = null, string $sewa = null): BookingDetail
    {
        $cust = User::factory()->create(['role' => 4]);
        $b = Booking::create(['user_id' => $cust->id, 'tanggal_booking' => $sewa ?? now(), 'asal_kota' => 1, 'jaminan' => 1,
            'status' => $status, 'total_harga' => 800000, 'uang_muka' => 400000]);

        return BookingDetail::create(['booking_id' => $b->id, 'mobil_id' => $mobil->id, 'supir_id' => $supirId, 'pakai_supir' => $supirId ? 1 : 0,
            'tanggal_sewa' => $sewa ?? now()->addDays(2), 'tanggal_kembali' => now()->parse($sewa ?? now()->addDays(2))->addDays(2), 'lama_sewa' => 2, 'status' => $status]);
    }

    // ───────── armada ─────────

    public function test_referensi_lists_types_models_and_allowed_statuses(): void
    {
        MasterMobil::factory()->create();
        $this->getJson('/api/admin/referensi')->assertOk()->assertJsonStructure(['data' => ['tipe', 'model', 'status']]);
    }

    public function test_mobil_create_is_live_immediately_and_validates_plate_and_uniqueness(): void
    {
        $this->postJson('/api/admin/mobils', $this->mobilPayload())->assertCreated()
            ->assertJsonPath('data.plat_nomor', 'AB 1234 CD')->assertJsonPath('data.status', Mobil::STATUS_TERSEDIA);
        $this->assertSame(Mobil::APPROVAL_APPROVED, Mobil::first()->status_approval);

        $this->postJson('/api/admin/mobils', $this->mobilPayload())->assertStatus(422)->assertJsonValidationErrors(['plat_nomor']);
        $this->postJson('/api/admin/mobils', $this->mobilPayload(['plat_nomor' => 'ab-1234']))->assertStatus(422)->assertJsonValidationErrors(['plat_nomor']);
        $this->postJson('/api/admin/mobils', $this->mobilPayload(['plat_nomor' => 'B 1 AA', 'tahun' => 1800, 'harga_sewa' => -1]))
            ->assertStatus(422)->assertJsonValidationErrors(['tahun', 'harga_sewa']);
    }

    public function test_mobil_update_search_filter_and_image_replace(): void
    {
        $id = $this->postJson('/api/admin/mobils', $this->mobilPayload(['merk' => 'Honda', 'plat_nomor' => 'B 99 ZZ']))->json('data.id');
        $this->postJson('/api/admin/mobils', $this->mobilPayload(['merk' => 'Toyota', 'plat_nomor' => 'AB 1 AA']))->assertCreated();

        $this->putJson("/api/admin/mobils/{$id}", $this->mobilPayload(['merk' => 'Honda', 'plat_nomor' => 'B 99 ZZ', 'harga_sewa' => 650000]))
            ->assertOk()->assertJsonPath('data.harga_sewa', 650000);
        // plat milik sendiri tidak dianggap kembar; milik mobil lain ditolak
        $this->putJson("/api/admin/mobils/{$id}", $this->mobilPayload(['plat_nomor' => 'AB 1 AA']))->assertStatus(422);

        $this->getJson('/api/admin/mobils?q=Honda')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/admin/mobils?q=AB 1')->assertJsonPath('meta.total', 1);
        $this->getJson('/api/admin/mobils?status=4')->assertJsonPath('meta.total', 0);
        $this->getJson('/api/admin/mobils?status=2')->assertStatus(422);

        $dibuat = [];
        try {
            $r1 = $this->post("/api/admin/mobils/{$id}/gambar", ['gambar' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertOk();
            $dibuat[] = basename($r1->json('data.gambar'));
            $this->assertFileExists(public_path('gambar_mobil/' . $dibuat[0]));

            $r2 = $this->post("/api/admin/mobils/{$id}/gambar", ['gambar' => UploadedFile::fake()->image('b.png')], ['Accept' => 'application/json'])->assertOk();
            $dibuat[] = basename($r2->json('data.gambar'));
            $this->assertFileDoesNotExist(public_path('gambar_mobil/' . $dibuat[0]), 'gambar lama dihapus');
            $this->post("/api/admin/mobils/{$id}/gambar", ['gambar' => UploadedFile::fake()->create('x.pdf', 5)], ['Accept' => 'application/json'])->assertStatus(422);
        } finally {
            foreach ($dibuat as $f) {
                File::delete(public_path('gambar_mobil/' . $f));
            }
        }
    }

    public function test_mobil_status_rules_and_delete_protection(): void
    {
        $bebas = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => 1]);
        $disewa = Mobil::factory()->create(['status' => Mobil::STATUS_DISEWA, 'status_approval' => 1]);
        $berpesanan = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => 1]);
        $this->sewa($berpesanan, Booking::STATUS_BOOKED);

        $this->patchJson("/api/admin/mobils/{$bebas->id}/status", ['status' => 4])->assertOk()->assertJsonPath('data.status', 4);
        $this->patchJson("/api/admin/mobils/{$bebas->id}/status", ['status' => 1])->assertOk();
        $this->patchJson("/api/admin/mobils/{$bebas->id}/status", ['status' => 3])->assertStatus(422);   // disewa hanya lewat jemput
        $this->patchJson("/api/admin/mobils/{$disewa->id}/status", ['status' => 1])->assertStatus(422);
        $this->patchJson("/api/admin/mobils/{$berpesanan->id}/status", ['status' => 4])->assertStatus(422);
        $this->patchJson("/api/admin/mobils/{$berpesanan->id}/status", ['status' => 1])->assertOk();

        $this->deleteJson("/api/admin/mobils/{$berpesanan->id}")->assertStatus(422);      // punya riwayat
        $this->deleteJson("/api/admin/mobils/{$bebas->id}")->assertOk();
        $this->assertNull(Mobil::find($bebas->id));
    }

    // ───────── sopir ─────────

    public function test_new_driver_can_log_in_immediately_and_deactivation_revokes_access(): void
    {
        Storage::fake('public');
        $id = $this->post('/api/admin/supirs', ['name' => 'Joko', 'email' => 'joko@example.test', 'password' => 'rahasia123', 'no_hp' => '0813',
            'alamat' => 'Bantul', 'gambar' => UploadedFile::fake()->image('j.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.status', 1)->assertJsonPath('data.aktif', true)->json('data.id');

        $this->postJson('/api/login', ['email' => 'joko@example.test', 'password' => 'rahasia123'])->assertOk()->assertJsonPath('data.user.role', 3);
        $this->assertSame(1, Supir::find($id)->status_approval);
        $this->postJson('/api/admin/supirs', ['name' => 'Dobel', 'email' => 'joko@example.test', 'password' => 'rahasia123', 'no_hp' => '1', 'alamat' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors(['email']);

        // dinonaktifkan → token dicabut dan tidak bisa login
        $this->putJson("/api/admin/supirs/{$id}", ['name' => 'Joko', 'email' => 'joko@example.test', 'no_hp' => '0813', 'alamat' => 'Bantul', 'aktif' => false])->assertOk()
            ->assertJsonPath('data.aktif', false);
        $this->assertSame(0, Supir::find($id)->user->tokens()->count());
        $this->postJson('/api/login', ['email' => 'joko@example.test', 'password' => 'rahasia123'])->assertForbidden();
    }

    public function test_driver_on_duty_cannot_be_deactivated_or_have_status_changed_and_list_filters_work(): void
    {
        $u = User::factory()->create(['role' => 3, 'name' => 'Bertugas Satu']);
        $sibuk = Supir::create(['user_id' => $u->id, 'status' => 2, 'status_approval' => 1]);
        $u2 = User::factory()->create(['role' => 3, 'name' => 'Libur Dua']);
        Supir::create(['user_id' => $u2->id, 'status' => 0, 'status_approval' => 1]);
        $data = ['name' => 'Bertugas Satu', 'email' => $u->email, 'no_hp' => '1', 'alamat' => 'x'];

        $this->putJson("/api/admin/supirs/{$sibuk->id}", $data + ['aktif' => false])->assertStatus(422);
        $this->putJson("/api/admin/supirs/{$sibuk->id}", $data + ['status' => 0])->assertStatus(422);
        $this->putJson("/api/admin/supirs/{$sibuk->id}", array_merge($data, ['no_hp' => '0899']))->assertOk()->assertJsonPath('data.no_hp', '0899');

        $this->getJson('/api/admin/supirs?status=2')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.nama', 'Bertugas Satu');
        $this->getJson('/api/admin/supirs?q=Libur')->assertJsonPath('meta.total', 1);
    }

    // ───────── penyewa ─────────

    public function test_penyewa_crud_is_limited_to_customers(): void
    {
        $data = ['name' => 'Budi', 'email' => 'budi@example.test', 'no_hp' => '0811', 'alamat' => 'Jl. A', 'asal_kota' => 'Sleman'];
        $id = $this->postJson('/api/admin/penyewa', $data)->assertCreated()->assertJsonPath('data.name', 'Budi')->json('data.id');

        // penyewa tidak punya kata sandi yang diketahui → tidak bisa login dengan tebakan
        $this->postJson('/api/login', ['email' => 'budi@example.test', 'password' => 'password'])->assertStatus(422);

        $this->postJson('/api/admin/penyewa', $data)->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->putJson("/api/admin/penyewa/{$id}", ['no_hp' => '0822'] + $data)->assertOk()->assertJsonPath('data.no_hp', '0822');
        $this->getJson('/api/admin/penyewa?q=Sleman')->assertOk();
        $this->getJson('/api/admin/penyewa?q=Budi')->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.jumlah_pesanan', 0);

        // akun staf bukan penyewa: tidak dapat dilihat atau diubah lewat endpoint ini
        $admin = User::factory()->create(['role' => 2]);
        $this->getJson("/api/admin/penyewa/{$admin->id}")->assertNotFound();
        $this->putJson("/api/admin/penyewa/{$admin->id}", $data)->assertNotFound();
    }

    // ───────── laporan kondisi oleh admin ─────────

    public function test_admin_reports_condition_for_self_drive_unit_and_lists_it(): void
    {
        Storage::fake('public');
        $mobil = Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);
        $detail = $this->sewa($mobil, Booking::STATUS_ONGOING);

        $this->post("/api/admin/detail/{$detail->id}/laporan-kondisi", ['jenis' => 1, 'kondisi' => 1, 'foto' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.jenis_label', 'Sebelum sewa');
        $this->postJson("/api/admin/detail/{$detail->id}/laporan-kondisi", ['jenis' => 2, 'kondisi' => 3, 'catatan' => 'Kaca pecah'])->assertCreated();

        $this->getJson("/api/admin/detail/{$detail->id}/laporan-kondisi")->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.kondisi_label', 'Rusak')->assertJsonPath('data.0.dibuat_oleh', auth('sanctum')->user()->name ?? $this->app['auth']->guard('sanctum')->user()->name);

        // laporan ikut tampil di detail pesanan (tanpa permintaan tambahan dari aplikasi)
        $this->getJson("/api/admin/bookings/{$detail->booking_id}")
            ->assertJsonPath('data.details.0.laporan_sebelum.kondisi_label', 'Baik')
            ->assertJsonPath('data.details.0.laporan_sesudah.kondisi_label', 'Rusak');

        $baru = $this->sewa($mobil, Booking::STATUS_BOOKED);
        $this->postJson("/api/admin/detail/{$baru->id}/laporan-kondisi", ['jenis' => 2, 'kondisi' => 1])->assertStatus(422);
        $this->assertSame(2, LaporanKondisi::count());
    }

    // ───────── rekap ─────────

    public function test_rekap_counts_the_month_correctly_excluding_canceled_and_using_real_rental_days(): void
    {
        $awal = now()->startOfMonth()->addDay()->setTime(9, 0);
        $mobil = Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);
        Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);   // armada = 2

        $done = $this->sewa($mobil, Booking::STATUS_DONE, null, $awal->format('Y-m-d H:i:s'));
        $this->sewa($mobil, Booking::STATUS_CANCELED, null, $awal->copy()->addDays(5)->format('Y-m-d H:i:s'));
        // bulan lain tidak ikut
        $this->sewa($mobil, Booking::STATUS_DONE, null, $awal->copy()->subMonths(2)->format('Y-m-d H:i:s'));

        Pembayaran::create(['booking_id' => $done->booking_id, 'jenis' => 1, 'jumlah' => 400000, 'status_pembayaran' => 1, 'tanggal_pembayaran' => $awal, 'jatuh_tempo' => $awal]);
        Pembayaran::create(['booking_id' => $done->booking_id, 'jenis' => 2, 'jumlah' => 500000, 'status_pembayaran' => 1, 'tanggal_pembayaran' => $awal->copy()->addDays(3), 'jatuh_tempo' => $awal]);
        Pembayaran::create(['booking_id' => $done->booking_id, 'jenis' => 2, 'jumlah' => 999999, 'status_pembayaran' => 0, 'jatuh_tempo' => $awal]);   // belum lunas: tidak dihitung
        Pengembalian::create(['booking_detail_id' => $done->id, 'tanggal_kembali_aktual' => $awal->copy()->addDays(3), 'kondisi' => 1, 'denda_flag' => 1, 'nominal_denda' => 100000]);

        $bulan = $awal->format('Y-m');
        $d = $this->getJson("/api/admin/rekap?bulan={$bulan}")->assertOk()->json('data');

        $this->assertSame(2, $d['pesanan']['total']);
        $this->assertSame(1, $d['pesanan']['selesai']);
        $this->assertSame(1, $d['pesanan']['dibatalkan']);
        $this->assertSame(400000, $d['pendapatan']['dp']);
        $this->assertSame(500000, $d['pendapatan']['pelunasan']);
        $this->assertSame(900000, $d['pendapatan']['total']);
        $this->assertSame(100000, $d['pendapatan']['denda_tercatat']);
        $this->assertSame(1, $d['armada']['total_unit_disewa']);          // yang dibatalkan tidak dihitung
        $this->assertSame(2, $d['armada']['total_hari_sewa']);            // sewa dua hari = 2, bukan 0
        $this->assertSame(1, $d['armada']['per_mobil'][0]['jumlah_sewa']);
        $this->assertEqualsWithDelta(2 / (2 * $awal->daysInMonth) * 100, $d['armada']['utilisasi_persen'], 0.1);

        $this->getJson('/api/admin/rekap?bulan=2026-13')->assertStatus(422);
        $this->getJson('/api/admin/rekap')->assertOk()->assertJsonPath('data.bulan', now()->format('Y-m'));
    }

    // ───────── pembatas peran ─────────

    public function test_new_admin_endpoints_are_closed_to_customer_and_driver(): void
    {
        $endpoints = [['GET', '/api/admin/referensi'], ['GET', '/api/admin/mobils'], ['POST', '/api/admin/mobils'], ['GET', '/api/admin/mobils/1'],
            ['PUT', '/api/admin/mobils/1'], ['DELETE', '/api/admin/mobils/1'], ['PATCH', '/api/admin/mobils/1/status'], ['POST', '/api/admin/mobils/1/gambar'],
            ['GET', '/api/admin/supirs'], ['POST', '/api/admin/supirs'], ['GET', '/api/admin/supirs/1'], ['PUT', '/api/admin/supirs/1'],
            ['GET', '/api/admin/penyewa'], ['POST', '/api/admin/penyewa'], ['GET', '/api/admin/penyewa/1'], ['PUT', '/api/admin/penyewa/1'],
            ['GET', '/api/admin/detail/1/laporan-kondisi'], ['POST', '/api/admin/detail/1/laporan-kondisi'], ['GET', '/api/admin/rekap']];

        $sopir = User::factory()->create(['role' => 3]);
        Supir::create(['user_id' => $sopir->id, 'status' => 1, 'status_approval' => 1]);
        foreach ([User::factory()->create(['role' => 4]), $sopir] as $bukanAdmin) {
            Sanctum::actingAs($bukanAdmin);
            foreach ($endpoints as [$m, $u]) {
                $this->json($m, $u)->assertForbidden();
            }
        }
    }
}
