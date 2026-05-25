<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Booking extends Model
{
    use HasFactory;

    /** 
     * ==============================
     *  Status Constants
     * ==============================
     */
    const STATUS_CANCELED = 0;
    const STATUS_BOOKED   = 1;
    const STATUS_ONGOING  = 2;
    const STATUS_DONE     = 3;

    /** 
     * ==============================
     *  Mass Assignment
     * ==============================
     */
    protected $fillable = [
        'user_id',
        'pakai_supir',
        'asal_kota',
        'nama_kota',
        'tanggal_booking',
        'status',       // 0=canceled, 1=booked, 2=ongoing, 3=done
        'jaminan',
        'uang_muka',
        'total_harga',
    ];

    /** 
     * ==============================
     *  Appends (Virtual Attributes)
     * ==============================
     */
    protected $appends = [
        'asal_kota_label',
        'jaminan_label',
        'tanggal_booking_format',
        'total_harga_rp',
        'uang_muka_rp',
        'status_label',
        'status_badge',
    ];

    /** 
     * ==============================
     *  Relationships
     * ==============================
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function details()
    {
        return $this->hasMany(BookingDetail::class);
    }
    public function pembayaranDp()
    {
        return $this->hasOne(Pembayaran::class)->where('jenis', 1);
    }
    public function pelunasan()
    {
        return $this->hasOne(Pembayaran::class)->where('jenis', 2);
    }


    /** 
     * ==============================
     *  Helpers (Static)
     * ==============================
     */
    public static function getStatusLabel($status): string
    {
        return match ($status) {
            self::STATUS_CANCELED => 'Canceled',
            self::STATUS_BOOKED   => 'Booked',
            self::STATUS_ONGOING  => 'On going',
            self::STATUS_DONE     => 'Done',
            default               => 'Unknown',
        };
    }

    /** 
     * ==============================
     *  Accessors
     * ==============================
     */

    // Label Kota
    protected function asalKotaLabel(): Attribute
    {
        return Attribute::get(
            fn() =>
            $this->asal_kota == 1 ? 'Yogyakarta' : $this->nama_kota
        );
    }
    protected function tanggalBookingFormat(): Attribute
    {
        return Attribute::get(fn() => $this->tanggal_booking
            ? Carbon::parse($this->tanggal_booking)->translatedFormat('l, d F Y H:i')
            : null);
    }

    // Label Jaminan
    protected function jaminanLabel(): Attribute
    {
        return Attribute::get(fn() => match ($this->jaminan) {
            1 => 'KTP/Passport',
            2 => 'KTP & Motor',
            default => '-',
        });
    }

    // Format Harga
    protected function totalHargaRp(): Attribute
    {
        return Attribute::get(
            fn() =>
            $this->total_harga !== null
                ? 'Rp ' . number_format($this->total_harga, 0, ',', '.')
                : '-'
        );
    }

    protected function uangMukaRp(): Attribute
    {
        return Attribute::get(
            fn() =>
            $this->uang_muka !== null
                ? 'Rp ' . number_format($this->uang_muka, 0, ',', '.')
                : '-'
        );
    }

    // Status
    protected function statusLabel(): Attribute
    {
        return Attribute::get(fn() => self::getStatusLabel($this->status));
    }

    protected function statusBadge(): Attribute
    {
        return Attribute::get(fn() => match ($this->status) {
            self::STATUS_CANCELED => '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">Canceled</span>',
            self::STATUS_BOOKED   => '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-700">Booked</span>',
            self::STATUS_ONGOING  => '<span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">On going</span>',
            self::STATUS_DONE     => '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">Done</span>',
            default               => '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">Unknown</span>',
        });
    }
    // Accessor: status dihitung dari details
    protected function computedStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $details = $this->details;

                if ($details->every(fn($d) => $d->status_detail === 0)) {
                    return 'Canceled';
                }

                if ($details->every(fn($d) => $d->status_detail === 3)) {
                    return 'Done';
                }

                if ($details->contains(fn($d) => $d->status_detail === 2)) {
                    return 'Ongoing';
                }

                return 'Booked'; // default kalau baru dibuat
            }
        );
    }
}
