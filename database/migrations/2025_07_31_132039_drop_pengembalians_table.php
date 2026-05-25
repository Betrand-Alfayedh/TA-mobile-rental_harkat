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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('pengembalians', function ($table) {
            $table->id();
            $table->unsignedBigInteger('transaksi_id');
            $table->date('tanggal_kembali_aktual');
            $table->string('gambar');
            $table->integer('status'); // 0 = rusak, 1 = baik
            $table->decimal('denda', 10, 2)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('cascade');
        });
    }
};
