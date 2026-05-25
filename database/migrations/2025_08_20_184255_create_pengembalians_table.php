<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengembalians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_detail_id')
                ->constrained('booking_details')
                ->onDelete('cascade');
            $table->date('tanggal_kembali_aktual');
            $table->tinyInteger('kondisi')->comment('0 = rusak, 1 = baik');
            $table->tinyInteger('denda')->comment('0 = aman, 1 = denda');
            $table->text('catatan')->nullable();
            $table->string('gambar')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengembalians');
    }
};
