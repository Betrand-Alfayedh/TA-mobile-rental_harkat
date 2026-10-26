<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\City;
use App\Models\MasterMobil;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\Supir;
use App\Models\TipeMobil;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Data tetap & idempotent: aman dijalankan berulang (php artisan db:seed).
     * Semua akun memakai password: "password".
     */
    public function run(): void
    {
        // ── Users (role: 1=SuperAdmin, 2=Admin, 3=Supir, 4=Customer) ──
        $users = [
            ['Super Admin',   'superadmin@example.com', 1, null,           null,                       null],
            ['Admin Harkat',  'admin@example.com',      2, null,           null,                       null],
            ['Budi Supir',    'supir1@example.com',     3, '081234567801', 'Jl. Kaliurang Km 5, Sleman', 'Yogyakarta'],
            ['Andi Supir',    'supir2@example.com',     3, '081234567802', 'Jl. Godean Km 3, Sleman',    'Yogyakarta'],
            ['Customer Demo', 'customer@example.com',   4, '081234567811', 'Jl. Malioboro No. 10',       'Yogyakarta'],
            ['Siti Customer', 'siti@example.com',       4, '081234567812', 'Jl. Dago No. 25, Bandung',   'Bandung'],
        ];

        foreach ($users as [$name, $email, $role, $noHp, $alamat, $kota]) {
            User::updateOrCreate(['email' => $email], [
                'name'              => $name,
                'password'          => Hash::make('password'),
                'role'              => $role,
                'no_hp'             => $noHp,
                'alamat'            => $alamat,
                'asal_kota'         => $kota,
                'email_verified_at' => now(),
            ]);
        }

        // ── Supir (status 1 = aktif, status_approval 1 = approved) ──
        foreach (User::where('role', 3)->get() as $u) {
            Supir::updateOrCreate(['user_id' => $u->id], ['status' => 1, 'status_approval' => 1]);
        }

        // ── Tipe & master mobil ──
        $tipe = [];
        foreach (['City Car', 'MPV', 'SUV', 'Mini Bus'] as $nama) {
            $tipe[$nama] = TipeMobil::firstOrCreate(['nama_tipe' => $nama])->id;
        }

        $master = [];
        foreach ([
            'New Agya'          => 'City Car',
            'New Brio'          => 'City Car',
            'New Avanza'        => 'MPV',
            'New Innova Reborn' => 'MPV',
            'New BR-V'          => 'MPV',
            'Raize'             => 'SUV',
            'New Fortuner GR'   => 'SUV',
            'Hiace Commuter'    => 'Mini Bus',
        ] as $nama => $namaTipe) {
            $master[$nama] = MasterMobil::updateOrCreate(['nama' => $nama], ['tipe_id' => $tipe[$namaTipe]])->id;
        }

        // ── Unit mobil (status 1 = tersedia, status_approval 1 = approved) ──
        // [master, plat, merk, tahun, harga_sewa, harga_all_in]
        $mobils = [
            ['New Agya',          'AB 1101 HA', 'Toyota',    2023,  350000,  550000],
            ['New Brio',          'AB 1102 HA', 'Honda',     2023,  400000,  600000],
            ['New Avanza',        'AB 1103 HA', 'Toyota',    2022,  450000,  650000],
            ['New Avanza',        'AB 1104 HA', 'Toyota',    2024,  475000,  675000],
            ['New Innova Reborn', 'AB 1105 HA', 'Toyota',    2022,  700000,  900000],
            ['New BR-V',          'AB 1106 HA', 'Honda',     2023,  550000,  750000],
            ['Raize',             'AB 1107 HA', 'Toyota',    2024,  500000,  700000],
            ['New Fortuner GR',   'AB 1108 HA', 'Toyota',    2023, 1200000, 1500000],
            ['Hiace Commuter',    'AB 1109 HA', 'Toyota',    2022, 1100000, 1400000],
            ['New Brio',          'AB 1110 HA', 'Honda',     2024,  400000,  600000],
        ];

        // Foto di public/gambar_mobil (Wikimedia Commons, CC BY-SA 4.0 / CC0)
        $foto = [
            'AB 1101 HA' => 'agya.jpg',    'AB 1102 HA' => 'brio1.jpg',
            'AB 1103 HA' => 'avanza1.jpg', 'AB 1104 HA' => 'avanza2.jpg',
            'AB 1105 HA' => 'innova.jpg',  'AB 1106 HA' => 'brv.jpg',
            'AB 1107 HA' => 'raize.jpg',   'AB 1108 HA' => 'fortuner.jpg',
            'AB 1109 HA' => 'hiace.jpg',   'AB 1110 HA' => 'brio2.jpg',
        ];

        $unit = [];
        foreach ($mobils as [$m, $plat, $merk, $tahun, $sewa, $allIn]) {
            $unit[$plat] = Mobil::updateOrCreate(['plat_nomor' => $plat], [
                'master_mobil_id' => $master[$m],
                'merk'            => $merk,
                'tahun'           => $tahun,
                'harga_sewa'      => $sewa,
                'harga_all_in'    => $allIn,
                'gambar'          => $foto[$plat],
                'status'          => Mobil::STATUS_TERSEDIA,
                'status_approval' => Mobil::APPROVAL_APPROVED,
            ]);
        }

        // ── Contoh booking (dibuat sekali saja) ──
        if (Booking::count() === 0) {
            $customer = User::where('email', 'customer@example.com')->first();
            $siti     = User::where('email', 'siti@example.com')->first();

            // 1) Booked: DP belum dibayar (untuk uji upload bukti di mobile)
            $this->booking($customer, $unit['AB 1103 HA'], now()->addDays(5), now()->addDays(7), false, 1, 2,
                Booking::STATUS_BOOKED, Mobil::STATUS_DIBOOKING, dp: 0, pelunasan: false);

            // 2) Booked: DP sudah diupload, menunggu verifikasi admin
            $this->booking($siti, $unit['AB 1107 HA'], now()->addDays(8), now()->addDays(10), true, 2, 1,
                Booking::STATUS_BOOKED, Mobil::STATUS_DIBOOKING, dp: 2, pelunasan: false, kota: 'Bandung');

            // 3) Done: DP & pelunasan lunas (untuk riwayat)
            $this->booking($customer, $unit['AB 1101 HA'], now()->subDays(12), now()->subDays(10), false, 1, 2,
                Booking::STATUS_DONE, Mobil::STATUS_TERSEDIA, dp: 1, pelunasan: true);
        }

        // ── Kota (48 ribu baris dari CSV, dilewati jika sudah terisi) ──
        if (City::count() === 0) {
            $this->call(CitiesSeeder::class);
        }
    }

    /** Buat 1 booking + 1 detail + pembayaran DP (+ pelunasan). dp: 0=belum, 2=pending, 1=lunas */
    private function booking(
        User $user, Mobil $mobil, Carbon $sewa, Carbon $kembali, bool $supir,
        int $asalKota, int $jaminan, int $status, int $statusMobil, int $dp, bool $pelunasan,
        ?string $kota = null,
    ): void {
        $lama  = max(1, (int) ceil($sewa->diffInHours($kembali) / 24));
        $total = ($supir ? $mobil->harga_all_in : $mobil->harga_sewa) * $lama;
        $muka  = (int) ($total / 2);

        $tglBooking = $sewa->isPast() ? $sewa->copy()->subDays(2) : now()->subDay();

        $booking = Booking::create([
            'user_id'         => $user->id,
            'tanggal_booking' => $tglBooking,
            'asal_kota'       => $asalKota,
            'nama_kota'       => $asalKota === 2 ? $kota : null,
            'jaminan'         => $jaminan,
            'status'          => $status,
            'total_harga'     => $total,
            'uang_muka'       => $muka,
        ]);

        BookingDetail::create([
            'booking_id'      => $booking->id,
            'mobil_id'        => $mobil->id,
            'pakai_supir'     => $supir ? 1 : 0,
            'tanggal_sewa'    => $sewa,
            'tanggal_kembali' => $kembali,
            'lama_sewa'       => $lama,
            'status'          => $status,
        ]);

        $mobil->update(['status' => $statusMobil]);

        Pembayaran::create([
            'booking_id'         => $booking->id,
            'jenis'              => 1,
            'jumlah'             => $muka,
            'status_pembayaran'  => $dp,
            'metode_pembayaran'  => $dp ? 2 : null,
            'tanggal_pembayaran' => $dp ? now()->subDay() : null,
            'jatuh_tempo'        => $tglBooking->copy()->addDays(4)->min($sewa->copy()->subHours(2)),
        ]);

        if ($pelunasan) {
            Pembayaran::create([
                'booking_id'         => $booking->id,
                'jenis'              => 2,
                'jumlah'             => $total - $muka,
                'status_pembayaran'  => 1,
                'metode_pembayaran'  => 1,
                'tanggal_pembayaran' => $sewa,
                'jatuh_tempo'        => $sewa,
            ]);
        }
    }
}
