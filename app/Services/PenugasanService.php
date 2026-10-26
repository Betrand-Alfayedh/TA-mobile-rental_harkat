<?php

namespace App\Services;

use App\Events\JobAssigned;
use App\Models\Booking;
use App\Models\BookingDetail;
use App\Models\JobOffer;
use App\Models\Supir;
use App\Notifications\JobAssignedEmail;
use Illuminate\Support\Facades\DB;

/**
 * Penugasan sopir: admin menawarkan unit bersopir, sopir menerima atau menolak.
 * Status tawaran: 0 terbuka · 1 diterima · 2 ditutup · 3 ditolak.
 */
class PenugasanService
{
    public const TERBUKA = 0;
    public const DITERIMA = 1;
    public const DITUTUP = 2;
    public const DITOLAK = 3;

    /** Tawarkan unit ke semua sopir yang tersedia dan disetujui. Mengembalikan jumlah sopir yang ditawari. */
    public function tawarkan(BookingDetail $detail): int
    {
        $detail->loadMissing('booking');

        if (! $detail->pakai_supir) {
            throw new \DomainException('Unit ini disewa tanpa sopir.');
        }
        if ($detail->supir_id) {
            throw new \DomainException('Unit ini sudah memiliki sopir.');
        }
        if ((int) $detail->status !== Booking::STATUS_BOOKED || $detail->booking?->status !== Booking::STATUS_BOOKED) {
            throw new \DomainException('Penugasan hanya untuk unit yang masih berstatus booked.');
        }

        // Sopir yang sudah menolak unit ini tidak ditawari lagi.
        $menolak = JobOffer::where('booking_detail_id', $detail->id)->where('status', self::DITOLAK)->pluck('supir_id');

        $sopirs = Supir::with('user')
            ->where('status', 1)
            ->where('status_approval', 1)
            ->whereNotIn('id', $menolak)
            ->whereHas('user', fn ($q) => $q->where('aktif', true))
            ->get();

        if ($sopirs->isEmpty()) {
            throw new \DomainException('Tidak ada supir yang available.');
        }

        $tawaran = DB::transaction(function () use ($detail, $sopirs) {
            return $sopirs->map(fn (Supir $s) => JobOffer::updateOrCreate(
                ['booking_detail_id' => $detail->id, 'supir_id' => $s->id],
                ['status' => self::TERBUKA]
            ));
        });

        foreach ($tawaran as $offer) {
            event(new JobAssigned($offer));
            if ($email = $offer->supir->user?->email) {
                $offer->supir->user->notify(new JobAssignedEmail($detail));
            }
        }

        return $tawaran->count();
    }

    /** Sopir menerima tawaran. Hanya satu sopir yang menang bila dua menerima bersamaan. */
    public function terima(int $offerId, Supir $supir): void
    {
        if ((int) $supir->status_approval !== 1) {
            throw new \DomainException('Akun supir belum disetujui.');
        }

        // Transaksi + kunci baris: penerimaan bersamaan diproses bergantian.
        $error = DB::transaction(function () use ($offerId, $supir) {
            $offer = JobOffer::where('id', $offerId)
                ->where('supir_id', $supir->id)
                ->where('status', self::TERBUKA)
                ->lockForUpdate()
                ->first();
            if (! $offer) {
                return ['Job sudah tidak tersedia.', 409];
            }

            $detail = BookingDetail::with('booking')->lockForUpdate()->find($offer->booking_detail_id);
            if (! $detail || $detail->supir_id || $detail->booking?->status !== Booking::STATUS_BOOKED) {
                $offer->update(['status' => self::DITUTUP]); // sudah diambil / booking tidak aktif
                return ['Job sudah diambil supir lain atau booking tidak aktif.', 409];
            }

            if ((int) $supir->fresh()->status !== 1) {
                return ['Anda harus berstatus tersedia untuk menerima job.', 422];
            }

            $detail->update(['pakai_supir' => 1, 'supir_id' => $supir->id]);
            $offer->update(['status' => self::DITERIMA]);

            JobOffer::where('booking_detail_id', $detail->id)
                ->where('id', '!=', $offer->id)
                ->where('status', self::TERBUKA)
                ->update(['status' => self::DITUTUP]);

            $supir->update(['status' => 2]);

            return null;
        });

        if ($error) {
            throw new \DomainException($error[0], $error[1]); // 409 = bersaing dengan sopir lain / sudah tidak tersedia
        }
    }

    /** Sopir menolak tawaran; admin dapat menawarkan ke sopir lain. */
    public function tolak(int $offerId, Supir $supir): void
    {
        $offer = JobOffer::where('id', $offerId)
            ->where('supir_id', $supir->id)
            ->where('status', self::TERBUKA)
            ->first();

        if (! $offer) {
            throw new \DomainException('Job sudah tidak tersedia.', 409);
        }

        $offer->update(['status' => self::DITOLAK]);
    }
}
