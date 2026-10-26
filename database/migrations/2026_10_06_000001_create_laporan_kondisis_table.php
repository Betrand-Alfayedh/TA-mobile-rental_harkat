<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_kondisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_detail_id')->constrained('booking_details')->cascadeOnDelete();
            $table->unsignedTinyInteger('jenis');    // 1 = sebelum sewa, 2 = sesudah sewa
            $table->unsignedTinyInteger('kondisi');  // 1 = baik, 2 = lecet/perlu perhatian, 3 = rusak
            $table->text('catatan')->nullable();
            $table->string('foto')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['booking_detail_id', 'jenis']); // satu laporan per jenis (kirim ulang = perbarui)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kondisis');
    }
};
