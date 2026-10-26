<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengembalian extends Model
{
    use HasFactory;

    /** Tarif denda keterlambatan per jam (rupiah). */
    public const DENDA_PER_JAM = 50000;

    protected $fillable = [
        'booking_detail_id',
        'tanggal_kembali_aktual',
        'kondisi',
        'denda_flag',
        'nominal_denda',
        'catatan',
        'gambar',
        'dicatat_oleh',
    ];

    // Relasi ke booking_detail
    public function bookingDetail()
    {
        return $this->belongsTo(BookingDetail::class);
    }

    // Accessor tanggal format Indonesia (contoh: 21 Agustus 2025)
    protected function tanggalKembaliAktual(): Attribute
    {
        return Attribute::make(
            get: fn($value) => Carbon::parse($value)->translatedFormat('d F Y H:i'),
        );
    }

    // Accessor kondisi (0 = rusak, 1 = baik → langsung return string)
    protected function kondisiLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->kondisi == 1 ? 'Baik' : 'Rusak',
        );
    }

    // Accessor denda dengan format rupiah
    protected function dendaFormat(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->denda_flag == 1 ? 'Denda' : '-',
        );
    }
}
