<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterMobil extends Model
{
    use HasFactory;
    protected $fillable = ['nama', 'tipe_id'];

    public function tipe()
    {
        return $this->belongsTo(TipeMobil::class, 'tipe_id');
    }

    public function mobils()
    {
        return $this->hasMany(Mobil::class, 'master_mobil_id');
    }
}
