<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MobilImage extends Model
{
    protected $fillable = ['mobil_id', 'image_path'];

    public function mobil()
    {
        return $this->belongsTo(Mobil::class);
    }
}
