<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use App\Models\Pembayaran;
use App\Models\JobOffer;
use App\Notifications\BookingCanceledNotification;
use App\Notifications\DpReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Satu-satunya tempat pembuatan booking (API mobile, web customer, web admin).
 */
class BookingService
{
    /** Customer harus booking minimal sekian jam sebelum sewa, supaya masih ada waktu membayar DP. */
    public const MIN_LEAD_HOURS = 3;

    /**
     * @param  array{asal_kota:int,nama_kota?:?string,jaminan:int,mobils:array}  $data  data tervalidasi
     * @throws \DomainException jika ada mobil yang tidak tersedia pada tanggal yang diminta
     */
    public function create(int $userId, array $data): Booking
    {
        $booking = DB::transaction(function () use ($userId, $data) {
            // Kunci baris mobil: dua booking bersamaan untuk mobil yang sama diproses bergantian,
            // sehingga pengecekan bentrok di bawah ini tidak bisa lolos dua-duanya.
            $mobils = Mobil::whereIn('id', collect($data['mobils'])->pluck('mobil_id')->unique())
                ->lockForUpdate()->get()->keyBy('id');

            $items = [];
            foreach ($data['mobils'] as $item) {
                $mobil  = $mobils[$item['mobil_id']];
                $sewa   = Carbon::parse($item['tanggal_sewa']);
                $kembali = Carbon::parse($item['tanggal_kembali']);

                if (! $mobil->isAvailableFor($sewa, $kembali)) {
                    throw new \DomainException(
                        "Mobil {$mobil->merk} ({$mobil->plat_nomor}) tidak tersedia pada tanggal yang dipilih."
                    );
                }

                $lama  = max(1, (int) ceil($sewa->diffInHours($kembali) / 24));
                $harga = $mobil->getHargaPerHari((bool) $item['pakai_supir']) * $lama;

                $items[] = compact('mobil', 'sewa', 'kembali', 'lama', 'harga') + ['supir' => (int) $item['pakai_supir']];
            }

            $total = array_sum(array_column($items, 'harga'));

            $booking = Booking::create([
                'user_id'         => $userId,
                'tanggal_booking' => now(),
                'total_harga'     => $total,
                'uang_muka'       => (int) ($total / 2),
                'status'          => Booking::STATUS_BOOKED,
                'asal_kota'       => $data['asal_kota'],
                'nama_kota'       => $data['nama_kota'] ?? null,
                'jaminan'         => $data['jaminan'],
            ]);

            foreach ($items as $i) {
                BookingDetail::create([
                    'booking_id'      => $booking->id,
                    'mobil_id'        => $i['mobil']->id,
                    'pakai_supir'     => $i['supir'],
                    'tanggal_sewa'    => $i['sewa'],
                    'tanggal_kembali' => $i['kembali'],
                    'lama_sewa'       => $i['lama'],
                    'status'          => Booking::STATUS_BOOKED,
                ]);
            }

            // Jatuh tempo DP: 4 hari, atau 2 jam sebelum sewa (mana yang lebih awal), minimal 1 jam dari sekarang
            // supaya booking walk-in (dibuat admin) tidak langsung dibatalkan oleh cron.
            $awal       = collect($items)->min(fn ($i) => $i['sewa']);
            $jatuhTempo = now()->addDays(4)->min($awal->copy()->subHours(2))->max(now()->addHour());

            Pembayaran::create([
                'booking_id'  => $booking->id,
                'jenis'       => 1,
                'jumlah'      => $booking->uang_muka,
                'jatuh_tempo' => $jatuhTempo,
            ]);

            return $booking;
        });

        $booking->load(['details.mobil.masterMobil.tipe', 'pembayaranDp', 'user']);

        // Kirim email setelah respons dikirim: SMTP lambat/gagal tidak boleh menggagalkan booking
        // yang sudah tersimpan atau membuat klien timeout.
        defer(function () use ($booking) {
            try {
                $booking->user->notify(new DpReminderNotification($booking, $booking->pembayaranDp));
            } catch (\Throwable $e) {
                Log::warning('Email DP gagal dikirim: ' . $e->getMessage());
            }
        });

        return $booking;
    }

    /**
     * Batalkan booking yang masih "booked": semua unit dibatalkan, tawaran job supir ditutup, supir dibebaskan,
     * lalu customer diberi tahu lewat email.
     *
     * @throws \DomainException jika booking tidak lagi berstatus booked
     */
    public function cancel(Booking $booking, string $reason): void
    {
        $booking = DB::transaction(function () use ($booking) {
            $locked = Booking::with('details.mobil')->lockForUpdate()->findOrFail($booking->id);

            if ($locked->status !== Booking::STATUS_BOOKED) {
                throw new \DomainException('Hanya booking berstatus Booked yang dapat dibatalkan.');
            }

            foreach ($locked->details as $detail) {
                $detail->update(['status' => Booking::STATUS_CANCELED]); // observer menyesuaikan booking
                JobOffer::where('booking_detail_id', $detail->id)->where('status', 0)->update(['status' => 2]);
                $detail->bebaskanSupir();

                // Baris lama yang masih bertanda "dibooking": kembalikan, tanpa menimpa maintenance/rusak/disewa.
                if ($detail->mobil?->status === Mobil::STATUS_DIBOOKING) {
                    $detail->mobil->update(['status' => Mobil::STATUS_TERSEDIA]);
                }
            }

            $locked->update(['status' => Booking::STATUS_CANCELED]);

            return $locked;
        });

        defer(function () use ($booking, $reason) {
            try {
                $booking->user->notify(new BookingCanceledNotification($booking, $reason));
            } catch (\Throwable $e) {
                Log::warning('Email pembatalan gagal dikirim: ' . $e->getMessage());
            }
        });
    }
}
