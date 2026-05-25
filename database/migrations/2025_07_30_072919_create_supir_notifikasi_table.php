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
        Schema::create('supir_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supir_id');
            $table->unsignedBigInteger('transaksi_id');
            $table->tinyInteger('status_response')->default(2); // 0 = tolak, 1 = terima, 2 = pending
            $table->timestamp('waktu_respon')->nullable();
            $table->timestamps();

            $table->foreign('supir_id')->references('id')->on('supirs')->onDelete('cascade');
            $table->foreign('transaksi_id')->references('id')->on('transaksis')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supir_notifikasi');
    }
};
