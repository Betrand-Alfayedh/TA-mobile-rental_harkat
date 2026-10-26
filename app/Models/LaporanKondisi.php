<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Laporan kondisi kendaraan per unit sewa: sebelum (jemput) dan sesudah (kembali). */
class LaporanKondisi extends Model
{
    public const SEBELUM = 1;
    public const SESUDAH = 2;

    public const KONDISI = [1 => 'Baik', 2 => 'Lecet / perlu perhatian', 3 => 'Rusak'];

    protected $table = 'laporan_kondisis';

    protected $fillable = ['booking_detail_id', 'jenis', 'kondisi', 'catatan', 'foto', 'dibuat_oleh'];

    public function bookingDetail()
    {
        return $this->belongsTo(BookingDetail::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function getKondisiLabelAttribute(): string
    {
        return self::KONDISI[(int) $this->kondisi] ?? '-';
    }

    public function getJenisLabelAttribute(): string
    {
        return (int) $this->jenis === self::SEBELUM ? 'Sebelum sewa' : 'Sesudah sewa';
    }
}
