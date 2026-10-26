<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\LaporanKondisi;
use App\Models\Mobil;
use App\Models\Supir;
use App\Models\User;
use App\Services\PenugasanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Fase 3: API sopir (tawaran terima/tolak, tugas, status, laporan kondisi). */
class SopirApiTest extends TestCase
{
    use RefreshDatabase;

    private function sopir(int $status = 1, int $approval = 1): Supir
    {
        $u = User::factory()->create(['role' => 3]);
        return Supir::create(['user_id' => $u->id, 'status' => $status, 'status_approval' => $approval]);
    }

    private function detail(int $status = Booking::STATUS_BOOKED, ?Supir $supir = null): BookingDetail
    {
        $cust = User::factory()->create(['role' => 4, 'no_hp' => '0812', 'alamat' => 'x', 'asal_kota' => 'x']);
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED]);
        $booking = Booking::create(['user_id' => $cust->id, 'tanggal_booking' => now(), 'asal_kota' => 1, 'jaminan' => 1,
            'status' => $status, 'total_harga' => 800000, 'uang_muka' => 400000]);

        return BookingDetail::create(['booking_id' => $booking->id, 'mobil_id' => $mobil->id, 'supir_id' => $supir?->id, 'pakai_supir' => 1,
            'tanggal_sewa' => now()->addDays(2), 'tanggal_kembali' => now()->addDays(4), 'lama_sewa' => 2, 'status' => $status]);
    }

    public function test_tawaran_lists_only_own_open_offers_without_financial_data(): void
    {
        $a = $this->sopir(); $b = $this->sopir();
        $detail = $this->detail();
        app(PenugasanService::class)->tawarkan($detail);

        Sanctum::actingAs($a->user);
        $res = $this->getJson('/api/sopir/tawaran')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.unit.penyewa.nama', $detail->booking->user->name);
        $this->assertStringNotContainsString('total_harga', $res->getContent());
        $this->assertStringNotContainsString('uang_muka', $res->getContent());
        $this->assertStringNotContainsString('harga', $res->getContent());

        // tawaran sopir lain tidak terlihat; setelah diambil sopir lain, hilang dari daftar
        app(PenugasanService::class)->terima(JobOffer::where('supir_id', $b->id)->value('id'), $b);
        $this->getJson('/api/sopir/tawaran')->assertJsonCount(0, 'data');
    }

    public function test_accept_wins_once_and_the_loser_gets_409(): void
    {
        $a = $this->sopir(); $b = $this->sopir();
        $detail = $this->detail();
        app(PenugasanService::class)->tawarkan($detail);
        $offerA = JobOffer::where('supir_id', $a->id)->value('id');
        $offerB = JobOffer::where('supir_id', $b->id)->value('id');

        Sanctum::actingAs($a->user);
        $this->postJson("/api/sopir/tawaran/{$offerA}/terima")->assertOk()->assertJsonPath('data.id', $detail->id);
        $this->assertSame($a->id, $detail->fresh()->supir_id);
        $this->assertSame(2, (int) $a->fresh()->status);

        Sanctum::actingAs($b->user);
        $this->postJson("/api/sopir/tawaran/{$offerB}/terima")->assertStatus(409);
        // tawaran milik sopir lain tidak bisa dijawab
        $this->postJson("/api/sopir/tawaran/{$offerA}/terima")->assertStatus(409);
        $this->postJson("/api/sopir/tawaran/{$offerA}/tolak")->assertStatus(409);
    }

    public function test_reject_marks_offer_and_cannot_be_repeated(): void
    {
        $a = $this->sopir();
        $detail = $this->detail();
        app(PenugasanService::class)->tawarkan($detail);
        $offer = JobOffer::where('supir_id', $a->id)->value('id');

        Sanctum::actingAs($a->user);
        $this->postJson("/api/sopir/tawaran/{$offer}/tolak")->assertOk();
        $this->assertSame(PenugasanService::DITOLAK, (int) JobOffer::find($offer)->status);
        $this->assertNull($detail->fresh()->supir_id);
        $this->postJson("/api/sopir/tawaran/{$offer}/tolak")->assertStatus(409);
        $this->postJson("/api/sopir/tawaran/{$offer}/terima")->assertStatus(409);
    }

    public function test_status_toggle_is_locked_while_on_duty_and_validated(): void
    {
        $libur = $this->sopir(1);
        Sanctum::actingAs($libur->user);
        $this->patchJson('/api/sopir/status', ['status' => 0])->assertOk()->assertJsonPath('data.status', 0);
        $this->patchJson('/api/sopir/status', ['status' => 1])->assertOk()->assertJsonPath('data.status', 1);
        $this->patchJson('/api/sopir/status', ['status' => 2])->assertStatus(422);

        $bertugas = $this->sopir(2);
        Sanctum::actingAs($bertugas->user);
        $this->patchJson('/api/sopir/status', ['status' => 0])->assertStatus(422);
        $this->assertSame(2, (int) $bertugas->fresh()->status);
    }

    public function test_tugas_and_riwayat_show_only_own_units(): void
    {
        $a = $this->sopir(2); $b = $this->sopir(2);
        $aktifA = $this->detail(Booking::STATUS_BOOKED, $a);
        $selesaiA = $this->detail(Booking::STATUS_DONE, $a);
        $batalA = $this->detail(Booking::STATUS_CANCELED, $a);
        $this->detail(Booking::STATUS_BOOKED, $b);

        Sanctum::actingAs($a->user);
        $this->getJson('/api/sopir/tugas')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $aktifA->id);
        $this->getJson('/api/sopir/riwayat')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $selesaiA->id);
        $this->getJson('/api/sopir/dashboard')->assertOk()
            ->assertJsonPath('data.tugas_aktif', 1)->assertJsonPath('data.tugas_selesai', 1)->assertJsonPath('data.tawaran_baru', 0)
            ->assertJsonPath('data.sopir.status', 2);
    }

    public function test_driver_reports_condition_with_photo_and_resending_replaces_it(): void
    {
        Storage::fake('public');
        $s = $this->sopir(2);
        $detail = $this->detail(Booking::STATUS_BOOKED, $s);
        Sanctum::actingAs($s->user);

        $res = $this->post("/api/sopir/tugas/{$detail->id}/laporan-kondisi",
            ['jenis' => 1, 'kondisi' => 1, 'catatan' => 'Mulus', 'foto' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])
            ->assertCreated()->assertJsonPath('data.kondisi_label', 'Baik');
        $fotoLama = LaporanKondisi::first()->foto;
        Storage::disk('public')->assertExists($fotoLama);

        // kirim ulang jenis yang sama → diperbarui, bukan dobel; foto lama dihapus
        $this->post("/api/sopir/tugas/{$detail->id}/laporan-kondisi",
            ['jenis' => 1, 'kondisi' => 2, 'catatan' => 'Ada lecet', 'foto' => UploadedFile::fake()->image('b.jpg')], ['Accept' => 'application/json'])->assertCreated();
        $this->assertSame(1, LaporanKondisi::count());
        $this->assertSame(2, (int) LaporanKondisi::first()->kondisi);
        Storage::disk('public')->assertMissing($fotoLama);

        // tampil di tugas
        $this->getJson('/api/sopir/tugas')->assertJsonPath('data.0.laporan_sebelum.kondisi', 2);
    }

    public function test_condition_report_rules(): void
    {
        Storage::fake('public');
        $s = $this->sopir(2);
        $milik = $this->detail(Booking::STATUS_BOOKED, $s);
        $orangLain = $this->detail(Booking::STATUS_BOOKED, $this->sopir(2));
        $batal = $this->detail(Booking::STATUS_CANCELED, $s);
        $selesai = $this->detail(Booking::STATUS_DONE, $s);
        Sanctum::actingAs($s->user);

        // sesudah sewa baru bisa setelah dijemput
        $this->postJson("/api/sopir/tugas/{$milik->id}/laporan-kondisi", ['jenis' => 2, 'kondisi' => 1])->assertStatus(422);
        // unit sopir lain → tidak ditemukan
        $this->postJson("/api/sopir/tugas/{$orangLain->id}/laporan-kondisi", ['jenis' => 1, 'kondisi' => 1])->assertNotFound();
        // dibatalkan, dan "sebelum" setelah selesai → ditolak
        $this->postJson("/api/sopir/tugas/{$batal->id}/laporan-kondisi", ['jenis' => 1, 'kondisi' => 1])->assertStatus(422);
        $this->postJson("/api/sopir/tugas/{$selesai->id}/laporan-kondisi", ['jenis' => 1, 'kondisi' => 1])->assertStatus(422);
        $this->postJson("/api/sopir/tugas/{$selesai->id}/laporan-kondisi", ['jenis' => 2, 'kondisi' => 3])->assertCreated();
        // validasi masukan
        $this->postJson("/api/sopir/tugas/{$milik->id}/laporan-kondisi", ['jenis' => 3, 'kondisi' => 9])->assertStatus(422)->assertJsonValidationErrors(['jenis', 'kondisi']);
        $this->post("/api/sopir/tugas/{$milik->id}/laporan-kondisi", ['jenis' => 1, 'kondisi' => 1, 'foto' => UploadedFile::fake()->create('x.pdf', 10)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['foto']);
    }

    public function test_sopir_endpoints_are_closed_to_other_roles_and_to_unapproved_drivers(): void
    {
        $endpoints = [['GET', '/api/sopir/dashboard'], ['PATCH', '/api/sopir/status'], ['GET', '/api/sopir/tawaran'],
            ['POST', '/api/sopir/tawaran/1/terima'], ['POST', '/api/sopir/tawaran/1/tolak'],
            ['GET', '/api/sopir/tugas'], ['GET', '/api/sopir/riwayat'], ['POST', '/api/sopir/tugas/1/laporan-kondisi']];

        foreach ($endpoints as [$m, $u]) {
            $this->json($m, $u)->assertUnauthorized();
        }
        foreach ([User::factory()->create(['role' => 2]), User::factory()->create(['role' => 4])] as $lain) {
            Sanctum::actingAs($lain);
            foreach ($endpoints as [$m, $u]) {
                $this->json($m, $u)->assertForbidden();
            }
        }

        // token yang sudah terbit tidak berlaku lagi bila persetujuan dicabut / akun dinonaktifkan
        $s = $this->sopir();
        Sanctum::actingAs($s->user);
        $this->getJson('/api/sopir/dashboard')->assertOk();
        $s->update(['status_approval' => 2]);
        Sanctum::actingAs($s->user->fresh()); // request nyata selalu memuat ulang pengguna
        $this->getJson('/api/sopir/dashboard')->assertForbidden();
    }
}
