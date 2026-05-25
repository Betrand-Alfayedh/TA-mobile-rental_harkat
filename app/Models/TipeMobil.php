<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipeMobil extends Model
{
    use HasFactory;
    protected $table = 'tipe_mobils'; // opsional sih, default-nya udah bener
    protected $fillable = [
        'nama_tipe'
    ];

    public function mobils()
    {
        return $this->hasMany(Mobil::class);
    }
    public function masterMobils()
    {
        return $this->hasMany(MasterMobil::class, 'tipe_id');
    }
}
