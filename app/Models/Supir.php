<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $nama
 * @property string $no_hp
 * @property string $alamat
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $gambar
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereAlamat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereGambar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereNama($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereNoHp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supir whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaksi> $transaksis
 * @property-read int|null $transaksis_count
 * @mixin \Eloquent
 */
class Supir extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'supirs'; // opsional sih, default-nya udah bener
    protected $fillable = [
        'user_id',
        'status',
        'status_approval',
        'gambar'
    ];

    protected $hidden = ['password'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function jobOffers()
    {
        return $this->hasMany(JobOffer::class);
    }
    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function getIsAvailableAttribute()
    {
        return $this->status == 1;
    }


    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            0 => 'Unavailable',
            1 => 'Available',
            2 => 'Bertugas',
            default => 'Status tidak diketahui',
        };
    }

    public function getStatusBadgeClassAttribute()
    {
        return match ($this->status) {
            0 => 'bg-red-100 text-red-800',
            1 => 'bg-green-100 text-green-800',
            2 => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-400',
        };
    }
    public function getStatusApprovalTextAttribute()
    {
        return match ($this->status_approval) {
            0 => 'Rejected by SuperAdmin',
            1 => 'Approved',
            2 => 'Pending Approval',
            default => 'Unknown',
        };
    }
}
