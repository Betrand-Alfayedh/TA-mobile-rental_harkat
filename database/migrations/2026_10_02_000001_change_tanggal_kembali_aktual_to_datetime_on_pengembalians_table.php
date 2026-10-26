<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kolom bertipe date membuang jam, padahal denda dihitung per jam.
    public function up(): void
    {
        Schema::table('pengembalians', function (Blueprint $table) {
            $table->dateTime('tanggal_kembali_aktual')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pengembalians', function (Blueprint $table) {
            $table->date('tanggal_kembali_aktual')->change();
        });
    }
};
