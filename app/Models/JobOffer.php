<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobOffer extends Model
{
    protected $fillable = ['booking_detail_id', 'supir_id', 'status']; //0=pending, 1=accepted, 2=closed,
    public function booking_detail()
    {
        return $this->belongsTo(BookingDetail::class, 'booking_detail_id');
    }

    public function supir()
    {
        return $this->belongsTo(Supir::class);
    }
}
