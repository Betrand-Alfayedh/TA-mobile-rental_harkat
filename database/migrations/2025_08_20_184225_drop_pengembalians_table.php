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
        Schema::dropIfExists('pengembalians');
    }

    public function down(): void
    {
        Schema::create('pengembalians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->onDelete('cascade');
            $table->date('tanggal_kembali_aktual');
            $table->tinyInteger('kondisi')->comment('0 = rusak, 1 = baik');
            $table->decimal('denda', 10, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->string('gambar')->nullable();
            $table->timestamps();
        });
    }
};
