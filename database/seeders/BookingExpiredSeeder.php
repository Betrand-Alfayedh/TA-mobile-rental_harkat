<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookingExpiredSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ambil 1 mobil random yg tersedia
        $mobil = Mobil::inRandomOrder()->first();

        if (!$mobil) {
            $this->command->warn("⚠️ Tidak ada mobil di database, seed mobil dulu brok!");
            return;
        }
        // cek atau buat user dummy
        $user = User::find(5);
        if (!$user) {
            $user = User::create([
                'id' => 5,
                'name' => 'Dummy User',
                'email' => 'dummy5@example.com',
                'password' => bcrypt('password'),
            ]);
        }
        // tandain mobil dipakai (0 = tidak tersedia)
        $mobil->update(['status' => 0]);
        // bikin booking dummy
        $booking = Booking::create([
            'user_id' => $user->id, // asumsi user id 1 ada
            'asal_kota' => 1,
            'status' => 1, // status booking awal
            'tanggal_booking' => Carbon::now()->subDays(2), // booking 2 hari lalu
            'jaminan' => 1,
            'uang_muka' => 750000,
            'total_harga' => 1500000,
        ]);

        // relasi booking detail
        BookingDetail::create([
            'booking_id' => $booking->id,
            'mobil_id' => $mobil->id,
            'pakai_supir' => 0,
            'tanggal_sewa' => Carbon::now()->addDay(),
            'tanggal_kembali' => Carbon::now()->addDays(3),
            'lama_sewa' => 2,
        ]);

        // pembayaran dummy (belum bayar)
        Pembayaran::create([
            'booking_id' => $booking->id,
            'tanggal_pembayaran' => null,
            'jumlah' => 750000,
            'metode_pembayaran' => 3, // QRIS
            'jenis' => 1, // DP
            'status_pembayaran' => 0, // belum bayar
            'foto_bukti' => null,
            'jatuh_tempo' => Carbon::now()->subDay()->addHours(2), // expired kemarin
        ]);

        $this->command->info("✅ Dummy booking expired berhasil dibuat (ID Booking: {$booking->id}, Mobil: {$mobil->nama_mobil})");
    }
}
