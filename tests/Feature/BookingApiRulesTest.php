<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Black box pemesanan pelanggan lewat API: biaya, DP, validasi masukan, bentrok jadwal, dan kepemilikan data. */
class BookingApiRulesTest extends TestCase
{
    use RefreshDatabase;

    private function pelanggan(array $o = []): User
    {
        return User::factory()->create($o + ['role' => 4, 'no_hp' => '0812', 'alamat' => 'Jl. A', 'asal_kota' => 'Yogyakarta']);
    }

    private function mobil(array $o = []): Mobil
    {
        return Mobil::factory()->create($o + ['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED,
            'harga_sewa' => 500000, 'harga_all_in' => 800000]);
    }

    private function item(Mobil $m, $sewa, $kembali, int $sopir = 0): array
    {
        return ['mobil_id' => $m->id, 'pakai_supir' => $sopir,
            'tanggal_sewa' => $sewa->format('Y-m-d H:i:s'), 'tanggal_kembali' => $kembali->format('Y-m-d H:i:s')];
    }

    private function payload(array $mobils, array $o = []): array
    {
        return $o + ['asal_kota' => 1, 'jaminan' => 1, 'mobils' => $mobils];
    }

    // ───────── estimasi biaya ─────────

    public function test_estimate_rounds_up_per_24_hours_with_a_minimum_of_one_day_and_dp_is_half(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(2)->startOfHour();

        $hitung = fn (int $jam, int $sopir = 0) => $this->postJson('/api/bookings/estimate', ['mobils' => [$this->item($m, $mulai, $mulai->copy()->addHours($jam), $sopir)]])
            ->assertOk()->json('data');

        $this->assertSame(500000, $hitung(5)['total'], 'kurang dari 24 jam tetap 1 hari');
        $this->assertSame(500000, $hitung(24)['total'], 'tepat 24 jam = 1 hari');
        $this->assertSame(1000000, $hitung(25)['total'], '25 jam dibulatkan ke 2 hari');
        $this->assertSame(1500000, $hitung(72)['total']);
        $this->assertSame(250000, $hitung(24)['uang_muka'], 'DP = 50%');
        $this->assertSame(1600000, $hitung(48, 1)['total'], 'dengan sopir memakai tarif all-in');
    }

    public function test_estimate_sums_multiple_cars_and_odd_totals_round_dp_down(): void
    {
        $a = $this->mobil(['harga_sewa' => 500000]);
        $b = $this->mobil(['harga_sewa' => 333333]);
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(2)->startOfHour();

        $r = $this->postJson('/api/bookings/estimate', ['mobils' => [
            $this->item($a, $mulai, $mulai->copy()->addDay()),
            $this->item($b, $mulai, $mulai->copy()->addDay()),
        ]])->assertOk()->json('data');

        $this->assertSame(833333, $r['total']);
        $this->assertSame(416666, $r['uang_muka']);   // dibulatkan ke bawah
        $this->assertCount(2, $r['items']);
    }

    // ───────── pembuatan pesanan ─────────

    public function test_valid_booking_is_created_with_total_dp_and_a_payment_due_date(): void
    {
        $m = $this->mobil();
        $user = $this->pelanggan();
        Sanctum::actingAs($user);
        $mulai = now()->addDays(10)->startOfHour();

        $res = $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDays(2))]))->assertCreated();

        $booking = Booking::findOrFail($res->json('data.id'));
        $this->assertSame(Booking::STATUS_BOOKED, $booking->status);
        $this->assertSame($user->id, (int) $booking->user_id);
        $this->assertSame(1000000, (int) $booking->total_harga);
        $this->assertSame(500000, (int) $booking->uang_muka);

        $dp = Pembayaran::where('booking_id', $booking->id)->where('jenis', 1)->firstOrFail();
        $this->assertSame(500000, (int) $dp->jumlah);
        $this->assertSame(0, (int) $dp->status_pembayaran);
        // jatuh tempo = 4 hari dari sekarang (sewa masih jauh), bukan lebih lama
        $this->assertEqualsWithDelta(now()->addDays(4)->timestamp, $dp->jatuh_tempo->timestamp, 120);
        $this->assertSame(2, (int) BookingDetail::where('booking_id', $booking->id)->value('lama_sewa'));
    }

    public function test_payment_due_date_is_two_hours_before_pickup_when_pickup_is_soon(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addHours(10);

        $id = $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDay())]))->assertCreated()->json('data.id');

        $dp = Pembayaran::where('booking_id', $id)->firstOrFail();
        $this->assertEqualsWithDelta($mulai->copy()->subHours(2)->timestamp, $dp->jatuh_tempo->timestamp, 5);
    }

    public function test_booking_with_driver_uses_all_in_price(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(3)->startOfHour();

        $res = $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDay(), 1)]))->assertCreated();

        $this->assertSame(800000, (int) Booking::find($res->json('data.id'))->total_harga);
        $this->assertSame(1, (int) BookingDetail::where('booking_id', $res->json('data.id'))->value('pakai_supir'));
    }

    // ───────── validasi masukan ─────────

    public function test_pickup_less_than_three_hours_from_now_is_rejected(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());

        $mulai = now()->addHours(2);
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDay())]))
            ->assertStatus(422)->assertJsonValidationErrors(['mobils.0.tanggal_sewa']);

        $ok = now()->addHours(4);
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $ok, $ok->copy()->addDay())]))->assertCreated();
    }

    public function test_return_date_must_be_after_pickup(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(2);

        foreach ([$mulai->copy()->subDay(), $mulai->copy()] as $kembali) {
            $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $kembali)]))
                ->assertStatus(422)->assertJsonValidationErrors(['mobils.0.tanggal_kembali']);
        }
    }

    public function test_invalid_fields_are_rejected_with_field_errors(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(2);
        $baik = $this->item($m, $mulai, $mulai->copy()->addDay());

        $this->postJson('/api/bookings', ['asal_kota' => 1, 'jaminan' => 1, 'mobils' => []])->assertStatus(422)->assertJsonValidationErrors(['mobils']);
        $this->postJson('/api/bookings', $this->payload([$baik], ['jaminan' => 9]))->assertStatus(422)->assertJsonValidationErrors(['jaminan']);
        $this->postJson('/api/bookings', $this->payload([$baik], ['asal_kota' => 7]))->assertStatus(422)->assertJsonValidationErrors(['asal_kota']);
        $this->postJson('/api/bookings', $this->payload([$baik], ['asal_kota' => 2]))->assertStatus(422)->assertJsonValidationErrors(['nama_kota']);
        $this->postJson('/api/bookings', $this->payload([['pakai_supir' => 2] + $baik]))->assertStatus(422)->assertJsonValidationErrors(['mobils.0.pakai_supir']);
        $this->postJson('/api/bookings', $this->payload([['mobil_id' => 999999] + $baik]))->assertStatus(422)->assertJsonValidationErrors(['mobils.0.mobil_id']);
        $this->assertSame(0, Booking::count(), 'tidak ada pesanan setengah jadi');
    }

    public function test_same_car_twice_in_one_request_is_rejected(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(2);
        $i = $this->item($m, $mulai, $mulai->copy()->addDay());

        $this->postJson('/api/bookings', $this->payload([$i, $i]))->assertStatus(422)->assertJsonValidationErrors(['mobils.0.mobil_id']);
    }

    public function test_incomplete_profile_cannot_order_and_gets_a_machine_readable_code(): void
    {
        $m = $this->mobil();
        Sanctum::actingAs($this->pelanggan(['alamat' => null]));
        $mulai = now()->addDays(2);

        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDay())]))
            ->assertStatus(422)->assertJsonPath('code', 'INCOMPLETE_PROFILE');
        $this->assertSame(0, Booking::count());
    }

    // ───────── ketersediaan ─────────

    public function test_unavailable_cars_cannot_be_booked(): void
    {
        Sanctum::actingAs($this->pelanggan());
        $mulai = now()->addDays(2);

        foreach ([Mobil::STATUS_RUSAK, Mobil::STATUS_MAINTENANCE] as $status) {
            $m = $this->mobil(['status' => $status]);
            $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDay())]))
                ->assertStatus(422)->assertJsonFragment(['message' => "Mobil {$m->merk} ({$m->plat_nomor}) tidak tersedia pada tanggal yang dipilih."]);
        }

        $belumDisetujui = $this->mobil(['status_approval' => Mobil::APPROVAL_PENDING]);
        $this->postJson('/api/bookings', $this->payload([$this->item($belumDisetujui, $mulai, $mulai->copy()->addDay())]))->assertStatus(422);
        $this->assertSame(0, Booking::count());
    }

    public function test_overlapping_dates_are_rejected_but_adjacent_and_other_periods_are_allowed(): void
    {
        $m = $this->mobil();
        $mulai = now()->addDays(10)->startOfHour();
        Sanctum::actingAs($this->pelanggan());
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDays(3))]))->assertCreated();

        Sanctum::actingAs($this->pelanggan());
        // bersinggungan di tengah → ditolak
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai->copy()->addDay(), $mulai->copy()->addDays(2))]))->assertStatus(422);
        // menutupi seluruh periode → ditolak
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai->copy()->subDay(), $mulai->copy()->addDays(5))]))->assertStatus(422);
        // periode lain (setelahnya) → boleh
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai->copy()->addDays(5), $mulai->copy()->addDays(6))]))->assertCreated();
        // sebelum periode → boleh (mobil yang sudah dibooking bulan depan tetap bisa disewa hari ini)
        $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai->copy()->subDays(5), $mulai->copy()->subDays(4))]))->assertCreated();
    }

    public function test_one_conflicting_car_rejects_the_whole_order_atomically(): void
    {
        $bebas = $this->mobil();
        $terpakai = $this->mobil();
        $mulai = now()->addDays(10)->startOfHour();
        Sanctum::actingAs($this->pelanggan());
        $this->postJson('/api/bookings', $this->payload([$this->item($terpakai, $mulai, $mulai->copy()->addDays(2))]))->assertCreated();
        $sebelum = Booking::count();

        Sanctum::actingAs($this->pelanggan());
        $this->postJson('/api/bookings', $this->payload([
            $this->item($bebas, $mulai, $mulai->copy()->addDays(2)),
            $this->item($terpakai, $mulai, $mulai->copy()->addDays(2)),
        ]))->assertStatus(422);

        $this->assertSame($sebelum, Booking::count(), 'pesanan tidak boleh sebagian tersimpan');
        $this->assertSame(0, BookingDetail::where('mobil_id', $bebas->id)->count());
    }

    // ───────── kepemilikan data ─────────

    public function test_customer_cannot_read_or_cancel_someone_elses_booking(): void
    {
        $m = $this->mobil();
        $pemilik = $this->pelanggan();
        Sanctum::actingAs($pemilik);
        $mulai = now()->addDays(5);
        $id = $this->postJson('/api/bookings', $this->payload([$this->item($m, $mulai, $mulai->copy()->addDay())]))->assertCreated()->json('data.id');

        Sanctum::actingAs($this->pelanggan());
        $this->getJson("/api/bookings/{$id}")->assertNotFound();
        $this->postJson("/api/bookings/{$id}/cancel")->assertNotFound();
        $this->getJson("/api/bookings/{$id}/pembayaran")->assertNotFound();
        $this->getJson('/api/bookings')->assertJsonCount(0, 'data');

        Sanctum::actingAs($pemilik);
        $this->getJson("/api/bookings/{$id}")->assertOk();
    }
}
