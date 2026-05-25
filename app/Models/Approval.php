<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'approvable_id',
        'approvable_type',
        'requested_by',
        'approved_by',
        'status',
        'note',
        'old_data',
        'new_data',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function approvable()
    {
        return $this->morphTo();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ✅ Accessor status teks
    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            0 => 'Rejected',
            1 => 'Approved',
            2 => 'Pending',
            default => 'Unknown',
        };
    }

    // ✅ Accessor warna status (buat badge UI)
    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            0 => 'bg-red-100 text-red-700',
            1 => 'bg-green-100 text-green-700',
            2 => 'bg-yellow-100 text-yellow-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
