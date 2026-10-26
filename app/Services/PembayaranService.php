<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Booking;
use App\Models\Pembayaran;
use App\Models\User;
use App\Notifications\PembayaranDitolakNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Aturan pembayaran yang dipakai bersama oleh web admin dan API admin.
 * Semua pelanggaran aturan dilempar sebagai \DomainException (pesan siap tampil).
 */
class PembayaranService
{
    /** Pembayaran berstatus menunggu verifikasi (ada bukti) → lunas. */
    public function verifikasi(Pembayaran $pembayaran, ?User $oleh = null): Pembayaran
    {
        $pembayaran->loadMissing(['booking.user', 'booking.details.mobil']);

        if ((int) $pembayaran->status_pembayaran !== 2 || ! $pembayaran->foto_bukti) {
            throw new \DomainException('Pembayaran ini tidak dalam status menunggu verifikasi.');
        }
        if ($pembayaran->booking->status === Booking::STATUS_CANCELED) {
            throw new \DomainException('Booking sudah dibatalkan, pembayaran tidak dapat diverifikasi.');
        }

        return $this->selesaikan($pembayaran, null, $oleh);
    }

    /** Admin menerima pembayaran langsung (mis. tunai) tanpa bukti unggahan. */
    public function tandaiLunas(Pembayaran $pembayaran, int $metode, ?User $oleh = null): Pembayaran
    {
        $pembayaran->loadMissing(['booking.user', 'booking.details.mobil']);
        $booking = $pembayaran->booking;

        if ((int) $pembayaran->status_pembayaran !== 0) {
            throw new \DomainException('Hanya pembayaran yang belum dibayar yang dapat ditandai lunas (yang sudah ada bukti: gunakan Verifikasi).');
        }
        if ($booking->status === Booking::STATUS_CANCELED) {
            throw new \DomainException('Booking sudah dibatalkan.');
        }
        if ((int) $pembayaran->jenis === 2 && (int) $booking->pembayaranDp?->status_pembayaran !== 1) {
            throw new \DomainException('Selesaikan pembayaran DP terlebih dahulu.');
        }

        return $this->selesaikan($pembayaran, $metode, $oleh);
    }

    /** Kembalikan ke "belum bayar" agar pelanggan dapat mengunggah ulang. */
    public function tolak(Pembayaran $pembayaran, ?string $catatan = null, ?User $oleh = null): Pembayaran
    {
        if ((int) $pembayaran->status_pembayaran !== 2) {
            throw new \DomainException('Hanya pembayaran yang menunggu verifikasi yang bisa ditolak.');
        }

        $booking = $pembayaran->booking;

        // Jatuh tempo diperpanjang supaya cron tidak langsung membatalkan booking yang baru ditolak.
        $awalSewa   = $booking->details->min('tanggal_sewa');
        $jatuhTempo = now()->addDay();
        if ($awalSewa) {
            $jatuhTempo = $jatuhTempo->min(Carbon::parse($awalSewa)->subHours(2));
        }

        if ($pembayaran->foto_bukti) {
            Storage::disk('public')->delete($pembayaran->foto_bukti);
        }

        $pembayaran->update([
            'status_pembayaran' => 0,
            'foto_bukti'        => null,
            'catatan_admin'     => $catatan,
            'dicatat_oleh'      => $oleh?->id,
            // jangan pernah memperpendek batas yang sudah ada
            'jatuh_tempo'       => $jatuhTempo->max($pembayaran->jatuh_tempo ?? now())->max(now()->addHour()),
        ]);

        $booking->user->notify(new PembayaranDitolakNotification($pembayaran));

        return $pembayaran;
    }

    /** Kirim invoice PDF ke pelanggan. Mengembalikan pesan galat, atau null bila berhasil. */
    public function kirimInvoice(Pembayaran $pembayaran): ?string
    {
        try {
            $booking = $pembayaran->booking;
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('booking', 'pembayaran'))->output();
            Mail::to($booking->user->email)->send(new InvoiceMail($booking, $pembayaran, $pdf));
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return null;
    }

    /** Tandai lunas; untuk DP buat tagihan pelunasan sekali saja. Utuh atau tidak sama sekali. */
    private function selesaikan(Pembayaran $pembayaran, ?int $metode, ?User $oleh): Pembayaran
    {
        $booking = $pembayaran->booking;

        DB::transaction(function () use ($pembayaran, $booking, $metode, $oleh) {
            $pembayaran->update([
                'status_pembayaran'  => 1,
                'metode_pembayaran'  => $metode ?? $pembayaran->metode_pembayaran,
                'tanggal_pembayaran' => $pembayaran->tanggal_pembayaran ?? now(),
                'dicatat_oleh'       => $oleh?->id,
            ]);

            if ($pembayaran->jenis == 1) {
                Pembayaran::firstOrCreate(
                    ['booking_id' => $booking->id, 'jenis' => 2],
                    [
                        'jumlah'            => $booking->total_harga - $pembayaran->jumlah,
                        'status_pembayaran' => 0,
                        'jatuh_tempo'       => $booking->details->max('tanggal_kembali'),
                    ]
                );
            }
        });

        return $pembayaran;
    }
}
