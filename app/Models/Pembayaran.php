<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    protected $table = 'pembayarans';

    protected $fillable = [
        'booking_id',
        'tanggal_pembayaran',
        'jumlah',
        'metode_pembayaran',
        'jenis',
        'foto_bukti',
        'status_pembayaran',
        'catatan_admin',
        'jatuh_tempo',
        'keterangan',
        'dicatat_oleh',
    ];

    protected $casts = [
        'tanggal_pembayaran' => 'datetime',
        'jatuh_tempo' => 'datetime',
    ];

    // ✅ Accessor buat metode pembayaran
    public function getMetodePembayaranTextAttribute(): string
    {
        return match ($this->metode_pembayaran) {
            1 => 'Cash',
            2 => 'Transfer',
            3 => 'QRIS',
            default => 'Tidak Diketahui',
        };
    }
    public function getJenisTextAttribute()
    {
        return match ($this->jenis) {
            1 => 'DP',
            2 => 'Pelunasan',
            default => '-',
        };
    }

    // ✅ Accessor buat status pembayaran
    public function getStatusPembayaranTextAttribute()
    {
        // jika belum bayar & lewat jatuh tempo -> dianggap Cancel
        if ($this->status_pembayaran == 0 && Carbon::now()->greaterThan($this->jatuh_tempo)) {
            return 'CANCELED';
        }

        return match ($this->status_pembayaran) {
            0 => 'Belum Bayar',
            1 => 'Sudah Bayar',
            2 => 'Pending',
            default => '-',
        };
    }

    // ✅ Accessor format tanggal
    protected function tanggalPembayaranFormat(): Attribute
    {
        return Attribute::get(fn() => $this->tanggal_pembayaran
            ? Carbon::parse($this->tanggal_pembayaran)->translatedFormat('l, d F Y H:i')
            : null);
    }

    protected function jatuhTempoFormat(): Attribute
    {
        return Attribute::get(fn() => $this->jatuh_tempo
            ? Carbon::parse($this->jatuh_tempo)->translatedFormat('l, d F Y H:i')
            : null);
    }

    /** Alasan bukti bayar tidak boleh diupload sekarang, atau null jika boleh. */
    public function alasanTidakBisaUpload(): ?string
    {
        $booking = $this->booking;
        $isDp    = (int) $this->jenis === 1;

        if ($isDp && $booking->status !== Booking::STATUS_BOOKED) {
            return 'Booking ini sudah dibatalkan atau tidak aktif.';
        }
        if (! $isDp) { // pelunasan/denda: dibayar saat atau setelah sewa, setelah DP lunas
            if ($booking->status === Booking::STATUS_CANCELED) {
                return 'Booking ini sudah dibatalkan.';
            }
            if ((int) $booking->pembayaranDp?->status_pembayaran !== 1) {
                return 'Selesaikan pembayaran DP terlebih dahulu.';
            }
        }
        if ((int) $this->status_pembayaran === 1) {
            return 'Pembayaran sudah dikonfirmasi oleh admin.';
        }
        // Batas waktu hanya berlaku untuk DP (pelunasan tetap harus dibayar walau terlambat).
        if ($isDp && (int) $this->status_pembayaran === 0 && $this->jatuh_tempo && $this->jatuh_tempo->isPast()) {
            return 'Batas waktu pembayaran sudah lewat.';
        }
        return null;
    }

    // 🔗 Relasi
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
