<?php

namespace Tests\Feature;

use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Emoji berwarna & simbol dekoratif yang sebelumnya dipakai sebagai ikon. */
    private const EMOJI = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B50}\x{2B05}\x{25BC}\x{2630}]/u';

    private function assertNoEmoji($response): void
    {
        $this->assertSame(0, preg_match(self::EMOJI, strip_tags($response->getContent())),
            'Halaman masih memuat emoji sebagai ikon.');
    }

    public function test_landing_and_catalog_render_with_real_data_and_no_emoji(): void
    {
        $mobil = Mobil::factory()->create(['status' => Mobil::STATUS_TERSEDIA, 'status_approval' => Mobil::APPROVAL_APPROVED, 'harga_sewa' => 350000]);

        $home = $this->get('/')->assertOk()
            ->assertSee('Sewa mobil di Yogyakarta')
            ->assertSee($mobil->masterMobil->nama)
            ->assertSee('Rp 350.000');
        $this->assertNoEmoji($home);

        $this->assertNoEmoji($this->get('/mobil')->assertOk()->assertSee('Armada Harkat')->assertSee($mobil->masterMobil->nama));
        $this->assertNoEmoji($this->get("/mobil/{$mobil->id}")->assertOk()->assertSee($mobil->masterMobil->nama));
        $this->assertNoEmoji($this->get('/login/customer')->assertOk());
    }

    public function test_catalog_filters_by_type_and_search_and_returns_a_partial_for_ajax(): void
    {
        // dua model berbeda (factory biasa memakai satu master untuk keduanya)
        $a = Mobil::factory()->create(['status' => 1, 'status_approval' => 1, 'master_mobil_id' => MasterMobil::factory()->create()->id]);
        $b = Mobil::factory()->create(['status' => 1, 'status_approval' => 1, 'master_mobil_id' => MasterMobil::factory()->create()->id]);
        $a->masterMobil->update(['nama' => 'Mobil Alfa']);
        $b->masterMobil->update(['nama' => 'Mobil Beta']);

        $this->get('/mobil?search=Alfa')->assertSee('Mobil Alfa')->assertDontSee('Mobil Beta');

        // permintaan AJAX hanya mengembalikan daftar kartu, bukan seluruh halaman
        $partial = $this->get('/mobil?search=Beta', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $partial->assertSee('Mobil Beta')->assertDontSee('<html', false);

        $this->get('/mobil?search=tidak-ada')->assertSee('Mobil tidak ditemukan');
    }

    public function test_empty_fleet_shows_a_helpful_message_instead_of_breaking(): void
    {
        $this->get('/')->assertOk()->assertSee('Armada sedang diperbarui');
    }

    public function test_customer_pages_render_without_emoji(): void
    {
        Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);
        $user = User::factory()->create(['role' => 4, 'no_hp' => '0812', 'alamat' => 'Jl. A', 'asal_kota' => 'Yogyakarta']);

        foreach (['/home', '/booking', '/riwayat', '/profile', '/profile/edit', '/booking/create'] as $url) {
            $res = $this->actingAs($user)->get($url);
            $this->assertContains($res->getStatusCode(), [200, 302], "$url gagal");
            if ($res->getStatusCode() === 200) {
                $this->assertNoEmoji($res);
            }
        }

        // pelanggan yang login melihat menu akunnya, bukan tombol "Masuk"
        $this->actingAs($user)->get('/mobil')->assertSee('Pesanan')->assertSee('Riwayat')->assertSee('title="Keluar"', false);
    }

    public function test_error_page_uses_line_icons(): void
    {
        $res = $this->get('/halaman-yang-tidak-ada');
        $res->assertStatus(404);
        $this->assertNoEmoji($res);
    }

    private function bookingFor(User $user, int $status = \App\Models\Booking::STATUS_BOOKED, int $dp = 0): \App\Models\BookingDetail
    {
        $mobil = Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);
        $b = \App\Models\Booking::create(['user_id' => $user->id, 'tanggal_booking' => now(), 'asal_kota' => 1, 'jaminan' => 1,
            'status' => $status, 'total_harga' => 800000, 'uang_muka' => 400000]);
        \App\Models\Pembayaran::create(['booking_id' => $b->id, 'jenis' => 1, 'jumlah' => 400000,
            'status_pembayaran' => $dp, 'jatuh_tempo' => now()->addDays(2)]);

        return \App\Models\BookingDetail::create(['booking_id' => $b->id, 'mobil_id' => $mobil->id, 'pakai_supir' => 0,
            'tanggal_sewa' => now()->addDays(3), 'tanggal_kembali' => now()->addDays(5), 'lama_sewa' => 2, 'status' => $status]);
    }

    public function test_pesanan_riwayat_and_payment_pages_show_real_booking_data(): void
    {
        $user = User::factory()->create(['role' => 4, 'no_hp' => '0812', 'alamat' => 'Jl. A', 'asal_kota' => 'Yogyakarta']);
        $aktif = $this->bookingFor($user);
        $this->bookingFor($user, \App\Models\Booking::STATUS_CANCELED);

        $res = $this->actingAs($user)->get('/booking')->assertOk()
            ->assertSee('Pesanan saya')
            ->assertSee("Booking #{$aktif->booking_id}")
            ->assertSee('Rp 800.000')
            ->assertSee('Bayar DP')
            ->assertSee('Batalkan booking')
            ->assertDontSee('Dibatalkan'); // yang batal ada di riwayat, bukan di sini
        $this->assertNoEmoji($res);

        $res = $this->actingAs($user)->get('/riwayat')->assertOk()->assertSee('Riwayat pesanan')->assertSee('Dibatalkan')
            ->assertDontSee('Batalkan booking');
        $this->assertNoEmoji($res);

        $res = $this->actingAs($user)->get("/pembayaran/{$aktif->booking_id}")->assertOk()
            ->assertSee('Rp 400.000')->assertSee('1370016710069')->assertSee('Kirim bukti pembayaran');
        $this->assertNoEmoji($res);
    }

    public function test_payment_page_reflects_status_and_blocks_upload_when_not_allowed(): void
    {
        $user = User::factory()->create(['role' => 4]);

        $pending = $this->bookingFor($user, dp: 2);
        $this->actingAs($user)->get("/pembayaran/{$pending->booking_id}")->assertSee('Menunggu verifikasi admin')->assertDontSee('Kirim bukti pembayaran');

        $lunas = $this->bookingFor($user, dp: 1);
        $this->actingAs($user)->get("/pembayaran/{$lunas->booking_id}")->assertSee('sudah diverifikasi')->assertDontSee('Kirim bukti pembayaran');

        $batal = $this->bookingFor($user, \App\Models\Booking::STATUS_CANCELED);
        $this->actingAs($user)->get("/pembayaran/{$batal->booking_id}")->assertSee('sudah dibatalkan')->assertDontSee('Kirim bukti pembayaran');
    }

    public function test_booking_form_renders_with_summary_and_template_hooks_used_by_the_script(): void
    {
        Mobil::factory()->create(['status' => 1, 'status_approval' => 1]);
        $user = User::factory()->create(['role' => 4, 'no_hp' => '0812', 'alamat' => 'Jl. A', 'asal_kota' => 'Yogyakarta']);

        $res = $this->actingAs($user)->get('/booking/create')->assertOk()
            ->assertSee('Booking mobil')->assertSee('Ringkasan biaya')
            ->assertSee('data-check-url="' . route('booking.checkMobils') . '"', false);

        // elemen yang dicari booking.js harus ada, kalau tidak form tidak hidup
        foreach (['mobils-wrapper', 'indicator', 'prev-mobil', 'next-mobil', 'add-mobil', 'slideLabel', 'sumTotal', 'sumDp',
            'sumSisa', 'sumCount', 'totalHarga', 'asalKota', 'namaKotaWrapper', 'submitBooking', 'mobil-template'] as $id) {
            $res->assertSee('id="' . $id . '"', false);
        }
        foreach (['tanggal-sewa', 'tanggal-kembali', 'mobil-select', 'pakai-supir-checkbox', 'hidden-pakai-supir', 'lama-sewa',
            'total-harga-mobil', 'check-mobils', 'remove-mobil', 'cek-hasil', 'mobil-info', 'mobil-no'] as $cls) {
            $res->assertSee($cls, false);
        }
        $this->assertNoEmoji($res);
    }

    public function test_guest_header_offers_a_driver_login_link_and_it_leads_to_the_supir_form(): void
    {
        $this->get('/')->assertSee(route('login.form', ['role' => 'supir']), false)->assertSee('Portal supir');

        $this->get('/login/supir')->assertOk()->assertSee('Masuk sebagai Supir');
    }
}
