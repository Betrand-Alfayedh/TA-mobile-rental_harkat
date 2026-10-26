<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Black box unggah bukti pembayaran oleh pelanggan: format, ukuran, status, dan penggantian file. */
class PembayaranUploadApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Booking $booking;
    private Pembayaran $dp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->user = User::factory()->create(['role' => 4, 'no_hp' => '0812', 'alamat' => 'x', 'asal_kota' => 'x']);
        $mobil = Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);
        $this->booking = Booking::create(['user_id' => $this->user->id, 'tanggal_booking' => now(), 'asal_kota' => 1, 'jaminan' => 1,
            'status' => Booking::STATUS_BOOKED, 'total_harga' => 800000, 'uang_muka' => 400000]);
        BookingDetail::create(['booking_id' => $this->booking->id, 'mobil_id' => $mobil->id, 'pakai_supir' => 0,
            'tanggal_sewa' => now()->addDays(3), 'tanggal_kembali' => now()->addDays(5), 'lama_sewa' => 2, 'status' => Booking::STATUS_BOOKED]);
        $this->dp = Pembayaran::create(['booking_id' => $this->booking->id, 'jenis' => 1, 'jumlah' => 400000,
            'status_pembayaran' => 0, 'jatuh_tempo' => now()->addDays(2)]);
        Sanctum::actingAs($this->user);
    }

    private function unggah($file, array $extra = [])
    {
        return $this->post("/api/bookings/{$this->booking->id}/pembayaran/upload", ['bukti_bayar' => $file] + $extra, ['Accept' => 'application/json']);
    }

    public function test_valid_proof_moves_dp_to_waiting_verification_and_is_stored(): void
    {
        $this->unggah(UploadedFile::fake()->image('bukti.jpg'), ['metode_pembayaran' => 2])->assertOk();

        $dp = $this->dp->fresh();
        $this->assertSame(2, (int) $dp->status_pembayaran);
        $this->assertSame(2, (int) $dp->metode_pembayaran);
        Storage::disk('public')->assertExists($dp->foto_bukti);
    }

    public function test_default_method_is_qris_when_not_given(): void
    {
        $this->unggah(UploadedFile::fake()->image('bukti.png'))->assertOk();
        $this->assertSame(3, (int) $this->dp->fresh()->metode_pembayaran);
    }

    public function test_invalid_files_are_rejected_and_nothing_changes(): void
    {
        $this->unggah(UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'))->assertStatus(422)->assertJsonValidationErrors(['bukti_bayar']);
        $this->unggah(UploadedFile::fake()->create('bukti.gif', 100, 'image/gif'))->assertStatus(422)->assertJsonValidationErrors(['bukti_bayar']);
        $this->unggah(UploadedFile::fake()->image('besar.png')->size(6000))->assertStatus(422)->assertJsonValidationErrors(['bukti_bayar']);   // > 5 MB
        $this->post("/api/bookings/{$this->booking->id}/pembayaran/upload", [], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors(['bukti_bayar']);
        $this->unggah(UploadedFile::fake()->image('b.jpg'), ['metode_pembayaran' => 9])->assertStatus(422)->assertJsonValidationErrors(['metode_pembayaran']);

        $dp = $this->dp->fresh();
        $this->assertSame(0, (int) $dp->status_pembayaran);
        $this->assertNull($dp->foto_bukti);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_exactly_5_mb_is_accepted(): void
    {
        $this->unggah(UploadedFile::fake()->image('pas.jpg')->size(5120))->assertOk();
    }

    public function test_upload_is_blocked_once_dp_is_confirmed_or_overdue_or_booking_is_canceled(): void
    {
        $this->dp->update(['status_pembayaran' => 1]);
        $this->unggah(UploadedFile::fake()->image('a.jpg'))->assertStatus(422)->assertJsonPath('message', 'Pembayaran sudah dikonfirmasi oleh admin.');

        $this->dp->update(['status_pembayaran' => 0, 'jatuh_tempo' => now()->subHour()]);
        $this->unggah(UploadedFile::fake()->image('a.jpg'))->assertStatus(422)->assertJsonPath('message', 'Batas waktu pembayaran sudah lewat.');

        $this->dp->update(['jatuh_tempo' => now()->addDay()]);
        $this->booking->update(['status' => Booking::STATUS_CANCELED]);
        $this->unggah(UploadedFile::fake()->image('a.jpg'))->assertStatus(422)->assertJsonPath('message', 'Booking ini sudah dibatalkan atau tidak aktif.');
    }

    public function test_someone_elses_booking_is_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 4]));
        $this->unggah(UploadedFile::fake()->image('a.jpg'))->assertNotFound();
        $this->assertSame(0, (int) $this->dp->fresh()->status_pembayaran);
    }

    public function test_reupload_while_waiting_replaces_the_proof_and_deletes_the_old_file(): void
    {
        $this->unggah(UploadedFile::fake()->image('pertama.jpg'))->assertOk();
        $lama = $this->dp->fresh()->foto_bukti;

        $this->unggah(UploadedFile::fake()->image('kedua.jpg'))->assertOk();

        $baru = $this->dp->fresh()->foto_bukti;
        $this->assertNotSame($lama, $baru);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($baru);
        $this->assertCount(1, Storage::disk('public')->allFiles(), 'tidak boleh ada file yatim');
    }

    public function test_customer_can_upload_again_after_admin_rejects(): void
    {
        $this->unggah(UploadedFile::fake()->image('a.jpg'))->assertOk();

        Sanctum::actingAs(User::factory()->create(['role' => 2]));
        $this->postJson("/api/admin/pembayaran/{$this->dp->id}/tolak", ['catatan_admin' => 'Nominal kurang'])->assertOk();
        $this->assertSame(0, (int) $this->dp->fresh()->status_pembayaran);

        Sanctum::actingAs($this->user);
        $this->unggah(UploadedFile::fake()->image('b.jpg'))->assertOk();
        $this->assertSame(2, (int) $this->dp->fresh()->status_pembayaran);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }
}
