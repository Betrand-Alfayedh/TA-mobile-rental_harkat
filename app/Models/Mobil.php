<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mobil extends Model
{
    use HasFactory;

    protected $table = 'mobils';

    protected $fillable = [
        'master_mobil_id',
        'plat_nomor',
        'merk',
        'tahun',
        'harga_sewa',
        'harga_all_in',
        'status',
        'status_approval',
        'gambar',
    ];

    /**
     * Constants untuk status mobil
     */
    public const STATUS_RUSAK       = 0;
    public const STATUS_TERSEDIA    = 1;
    public const STATUS_DIBOOKING   = 2;
    public const STATUS_DISEWA      = 3;
    public const STATUS_MAINTENANCE = 4;

    /**
     * Constants untuk status approval
     */
    public const APPROVAL_REJECTED = 0;
    public const APPROVAL_APPROVED = 1;
    public const APPROVAL_PENDING  = 2;

    /**
     * Casts untuk fields numerik
     */
    protected $casts = [
        'status'          => 'integer',
        'status_approval' => 'integer',
        'harga_sewa'      => 'integer',
        'harga_all_in'    => 'integer',
        'tahun'           => 'integer',
    ];

    /**
     * Accessors
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUSAK       => 'Rusak',
            self::STATUS_TERSEDIA    => 'Tersedia',
            self::STATUS_DIBOOKING   => 'Telah dibooking',
            self::STATUS_DISEWA      => 'Telah disewa',
            self::STATUS_MAINTENANCE => 'Maintenance',
            default                  => 'Status tidak diketahui',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUSAK       => 'bg-red-100 text-red-800',
            self::STATUS_TERSEDIA    => 'bg-green-100 text-green-800',
            self::STATUS_DIBOOKING   => 'bg-amber-100 text-amber-800',
            self::STATUS_DISEWA      => 'bg-blue-100 text-blue-800',
            self::STATUS_MAINTENANCE => 'bg-gray-100 text-gray-800',
            default                  => 'bg-gray-100 text-gray-400',
        };
    }

    public function getStatusApprovalTextAttribute(): string
    {
        return match ($this->status_approval) {
            self::APPROVAL_REJECTED => 'Rejected by SuperAdmin',
            self::APPROVAL_APPROVED => 'Approved',
            self::APPROVAL_PENDING  => 'Pending Approval',
            default                 => 'Unknown',
        };
    }

    public function getHargaPerHari(bool $pakaiSupir = false): int
    {
        return $pakaiSupir
            ? ($this->harga_all_in ?? 0)
            : ($this->harga_sewa ?? 0);
    }

    /**
     * Relasi
     */
    public function masterMobil()
    {
        return $this->belongsTo(MasterMobil::class, 'master_mobil_id');
    }

    public function images()
    {
        return $this->hasMany(MobilImage::class);
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }

    public function bookingDetails()
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function type()
    {
        return $this->belongsTo(TipeMobil::class, 'tipe_id');
    }

    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Query scopes
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_TERSEDIA);
    }

    public function scopeApproved($query)
    {
        return $query->where('status_approval', self::APPROVAL_APPROVED);
    }
}
