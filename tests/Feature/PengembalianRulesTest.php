<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Pengembalian;
use App\Models\Supir;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Black box pengembalian: batas denda per menit, kondisi mobil, unit ganda, dan validasi. Lewat API admin. */
class PengembalianRulesTest extends TestCase
{
    use RefreshDatabase;

    private Carbon $rencana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rencana = now()->subDay()->startOfMinute();
        Sanctum::actingAs(User::factory()->create(['role' => 2]));
    }

    /** Unit yang sudah dijemput (Ongoing) dengan jadwal kembali di [$this->rencana]. */
    private function unitBerjalan(?Booking $booking = null, ?Supir $supir = null): BookingDetail
    {
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_DISEWA, 'status_approval' => 1]);
        $booking ??= Booking::create(['user_id' => User::factory()->create(['role' => 4])->id, 'tanggal_booking' => now()->subDays(3), 'asal_kota' => 1,
            'jaminan' => 1, 'status' => Booking::STATUS_ONGOING, 'total_harga' => 400000, 'uang_muka' => 200000]);
        if (! Pembayaran::where('booking_id', $booking->id)->exists()) {
            Pembayaran::create(['booking_id' => $booking->id, 'jenis' => 1, 'jumlah' => 200000, 'status_pembayaran' => 1, 'jatuh_tempo' => now()->subDays(2)]);
            Pembayaran::create(['booking_id' => $booking->id, 'jenis' => 2, 'jumlah' => 200000, 'status_pembayaran' => 0, 'jatuh_tempo' => now()]);
        }

        return BookingDetail::create(['booking_id' => $booking->id, 'mobil_id' => $mobil->id, 'supir_id' => $supir?->id, 'pakai_supir' => $supir ? 1 : 0,
            'tanggal_sewa' => $this->rencana->copy()->subDay(), 'tanggal_kembali' => $this->rencana, 'lama_sewa' => 1, 'status' => Booking::STATUS_ONGOING]);
    }

    private function kembalikan(BookingDetail $d, Carbon $aktual, int $kondisi = 1)
    {
        return $this->postJson("/api/admin/detail/{$d->id}/pengembalian", [
            'tanggal_kembali_aktual' => $aktual->format('Y-m-d H:i:s'), 'kondisi' => $kondisi,
        ]);
    }

    public function test_late_fee_boundaries_per_started_hour(): void
    {
        $kasus = [
            'lebih awal 5 jam'   => [-300, 0],
            'tepat waktu'        => [0, 0],
            'telat 1 menit'      => [1, 1],
            'telat 59 menit'     => [59, 1],
            'telat tepat 60 menit' => [60, 1],
            'telat 61 menit'     => [61, 2],
            'telat 90 menit'     => [90, 2],
            'telat 25 jam'       => [1500, 25],
        ];

        foreach ($kasus as $nama => [$menit, $jamDenda]) {
            $d = $this->unitBerjalan();
            $res = $this->kembalikan($d, $this->rencana->copy()->addMinutes($menit))->assertCreated();

            $this->assertSame($jamDenda, $res->json('data.jam_terlambat'), $nama);
            $this->assertSame($jamDenda * Pengembalian::DENDA_PER_JAM, $res->json('data.nominal_denda'), $nama);
            $this->assertSame($jamDenda > 0, (bool) Pengembalian::where('booking_detail_id', $d->id)->value('denda_flag'), $nama);
        }
    }

    public function test_late_fee_is_added_to_the_unpaid_settlement_and_on_time_does_not_change_it(): void
    {
        $telat = $this->unitBerjalan();
        $this->kembalikan($telat, $this->rencana->copy()->addMinutes(90))->assertCreated();
        $this->assertSame(200000 + 100000, (int) Pembayaran::where('booking_id', $telat->booking_id)->where('jenis', 2)->value('jumlah'));

        $tepat = $this->unitBerjalan();
        $this->kembalikan($tepat, $this->rencana)->assertCreated();
        $this->assertSame(200000, (int) Pembayaran::where('booking_id', $tepat->booking_id)->where('jenis', 2)->value('jumlah'));
    }

    public function test_late_fee_creates_a_separate_bill_when_settlement_is_already_paid(): void
    {
        $d = $this->unitBerjalan();
        Pembayaran::where('booking_id', $d->booking_id)->where('jenis', 2)->update(['status_pembayaran' => 1]);

        $this->kembalikan($d, $this->rencana->copy()->addMinutes(120))->assertCreated();

        $tagihanDenda = Pembayaran::where('booking_id', $d->booking_id)->where('keterangan', 'Denda keterlambatan')->first();
        $this->assertNotNull($tagihanDenda);
        $this->assertSame(100000, (int) $tagihanDenda->jumlah);
        $this->assertSame(0, (int) $tagihanDenda->status_pembayaran);
    }

    public function test_car_status_depends_on_condition_and_supir_is_freed(): void
    {
        $sopir = Supir::create(['user_id' => User::factory()->create(['role' => 3])->id, 'status' => 2, 'status_approval' => 1]);

        $baik = $this->unitBerjalan(null, $sopir);
        $this->kembalikan($baik, $this->rencana)->assertCreated();
        $this->assertSame(Mobil::STATUS_MAINTENANCE, $baik->mobil->fresh()->status, 'baik → dicek dulu (maintenance)');
        $this->assertSame(1, (int) $sopir->fresh()->status, 'sopir tersedia lagi');

        $rusak = $this->unitBerjalan();
        $this->kembalikan($rusak, $this->rencana, 0)->assertCreated();
        $this->assertSame(Mobil::STATUS_RUSAK, $rusak->mobil->fresh()->status);
    }

    public function test_order_is_done_only_when_every_unit_is_returned(): void
    {
        $booking = Booking::create(['user_id' => User::factory()->create(['role' => 4])->id, 'tanggal_booking' => now()->subDays(3), 'asal_kota' => 1,
            'jaminan' => 1, 'status' => Booking::STATUS_ONGOING, 'total_harga' => 800000, 'uang_muka' => 400000]);
        $a = $this->unitBerjalan($booking);
        $b = $this->unitBerjalan($booking);

        $this->kembalikan($a, $this->rencana)->assertCreated()->assertJsonPath('data.status_pesanan', 'On going');
        $this->assertSame(Booking::STATUS_ONGOING, $booking->fresh()->status);

        $this->kembalikan($b, $this->rencana)->assertCreated()->assertJsonPath('data.status_pesanan', 'Done');
        $this->assertSame(Booking::STATUS_DONE, $booking->fresh()->status);
    }

    public function test_rejections_leave_everything_unchanged(): void
    {
        $d = $this->unitBerjalan();

        // sebelum jadwal sewa
        $this->kembalikan($d, $d->tanggal_sewa->copy()->subHour())->assertStatus(422)
            ->assertJsonPath('message', 'Tanggal kembali aktual tidak boleh sebelum tanggal sewa.');
        // kondisi dan tanggal tidak valid
        $this->postJson("/api/admin/detail/{$d->id}/pengembalian", ['tanggal_kembali_aktual' => 'bukan-tanggal', 'kondisi' => 1])->assertStatus(422)->assertJsonValidationErrors(['tanggal_kembali_aktual']);
        $this->postJson("/api/admin/detail/{$d->id}/pengembalian", ['tanggal_kembali_aktual' => $this->rencana->toDateTimeString(), 'kondisi' => 5])->assertStatus(422)->assertJsonValidationErrors(['kondisi']);
        $this->postJson("/api/admin/detail/{$d->id}/pengembalian", ['kondisi' => 1])->assertStatus(422)->assertJsonValidationErrors(['tanggal_kembali_aktual']);

        $this->assertSame(0, Pengembalian::count());
        $this->assertSame(Booking::STATUS_ONGOING, (int) $d->fresh()->status);
        $this->assertSame(Mobil::STATUS_DISEWA, $d->mobil->fresh()->status);

        // sudah dikembalikan → tidak bisa dua kali
        $this->kembalikan($d, $this->rencana)->assertCreated();
        $this->kembalikan($d, $this->rencana)->assertStatus(422);
        $this->assertSame(1, Pengembalian::count());
    }

    public function test_unit_not_yet_picked_up_cannot_be_returned(): void
    {
        $d = $this->unitBerjalan();
        $d->update(['status' => Booking::STATUS_BOOKED]);

        $this->kembalikan($d, $this->rencana)->assertStatus(422);
        $this->assertSame(0, Pengembalian::count());
    }

    public function test_return_is_recorded_with_the_admin_who_made_it(): void
    {
        $admin = User::factory()->create(['role' => 2]);
        Sanctum::actingAs($admin);
        $d = $this->unitBerjalan();

        $this->kembalikan($d, $this->rencana)->assertCreated();

        $this->assertSame($admin->id, (int) Pengembalian::where('booking_detail_id', $d->id)->value('dicatat_oleh'));
    }
}
