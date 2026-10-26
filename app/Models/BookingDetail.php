<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'mobil_id',
        'pakai_supir',
        'supir_id',
        'tanggal_sewa',
        'tanggal_kembali',
        'status',
        'lama_sewa',
    ];

    protected $appends = [
        'tanggal_mulai_format',
        'tanggal_selesai_format',
        'tanggal_mulai_iso',
        'tanggal_selesai_iso',
        'status_label',
    ];

    public function getAsalKotaLabelAttribute()
    {
        if ($this->asal_kota == 1) {
            return 'Yogyakarta';
        }
        return $this->nama_kota;
    }
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            0 => 'Canceled',
            1 => 'Booked',
            2 => 'On Going',
            3 => 'Done',
            default => 'Tidak Diketahui',
        };
    }

    // ✅ Accessor tanggal format
    protected function tanggalMulaiFormat(): Attribute
    {
        return Attribute::get(fn() => $this->tanggal_sewa
            ? Carbon::parse($this->tanggal_sewa)->translatedFormat('l, d F Y H:i')
            : null);
    }
    protected function tanggalSelesaiFormat(): Attribute
    {
        return Attribute::get(fn() => $this->tanggal_kembali
            ? Carbon::parse($this->tanggal_kembali)->translatedFormat('l, d F Y H:i')
            : null);
    }
    protected function tanggalMulaiIso(): Attribute
    {
        return Attribute::get(fn() => $this->tanggal_sewa
            ? Carbon::parse($this->tanggal_sewa)->format('Y-m-d\TH:i')
            : null);
    }
    protected function tanggalSelesaiIso(): Attribute
    {
        return Attribute::get(fn() => $this->tanggal_kembali
            ? Carbon::parse($this->tanggal_kembali)->format('Y-m-d\TH:i')
            : null);
    }

    // Relasi ke Booking
    /** Supir kembali tersedia bila tidak punya job aktif lain (dipanggil saat unit dikembalikan/dibatalkan). */
    public function bebaskanSupir(): void
    {
        if (! $this->supir_id) {
            return;
        }

        $masihBertugas = self::where('supir_id', $this->supir_id)
            ->where('id', '!=', $this->id)
            ->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_ONGOING])
            ->exists();

        if (! $masihBertugas) {
            Supir::whereKey($this->supir_id)->where('status', 2)->update(['status' => 1]);
        }
    }

    /** Ada tawaran terbuka ke sopir dan belum ada yang menerima. */
    public function getMenungguSupirAttribute(): bool
    {
        return ! $this->supir_id && $this->jobOffers->where('status', 0)->isNotEmpty();
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
    // Relasi ke Mobil
    public function mobil()
    {
        return $this->belongsTo(Mobil::class);
    }
    public function laporanKondisis()
    {
        return $this->hasMany(LaporanKondisi::class);
    }
    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class, 'booking_detail_id');
    }
    // Relasi ke Supir
    public function supir()
    {
        return $this->belongsTo(Supir::class);
    }
    public function pengembalian()
    {
        return $this->hasOne(Pengembalian::class);
    }
}
