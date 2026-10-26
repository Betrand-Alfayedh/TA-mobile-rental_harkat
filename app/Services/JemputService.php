<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\Mobil;
use Illuminate\Support\Facades\DB;

class JemputService
{
    /** Konfirmasi unit dijemput: butuh DP terverifikasi; unit → ongoing, mobil → disewa. */
    public function konfirmasi(BookingDetail $detail): BookingDetail
    {
        $detail->loadMissing(['mobil', 'booking.pembayaranDp']);
        $mobil   = $detail->mobil;
        $booking = $detail->booking;

        // Ketersediaan ditentukan oleh tanggal booking, bukan status global "dibooking".
        $error = match (true) {
            ! $mobil || ! $booking => 'Data mobil atau booking tidak ditemukan.',
            (int) $detail->status !== Booking::STATUS_BOOKED => 'Unit ini tidak dalam status booked (sudah dijemput, selesai, atau dibatalkan).',
            (int) $booking->pembayaranDp?->status_pembayaran !== 1 => 'DP belum diverifikasi. Verifikasi pembayaran DP terlebih dahulu.',
            ! in_array($mobil->status, [Mobil::STATUS_TERSEDIA, Mobil::STATUS_DIBOOKING], true) => 'Mobil sedang disewa atau tidak tersedia.',
            default => null,
        };

        if ($error) {
            throw new \DomainException($error);
        }

        DB::transaction(function () use ($detail, $mobil) {
            $mobil->update(['status' => Mobil::STATUS_DISEWA]);
            $detail->update(['status' => Booking::STATUS_ONGOING]); // observer menyesuaikan status booking
        });

        return $detail;
    }
}
