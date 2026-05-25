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
        Schema::table('transaksis', function (Blueprint $table) {
        // Nama FK nya 'transaksis_pelanggan_id_foreign'
        $table->dropForeign('transaksis_pelanggan_id_foreign');
        $table->dropColumn('pelanggan_id'); // optional, kalau mau hapus kolomnya sekalian
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
        $table->unsignedBigInteger('pelanggan_id')->nullable();
        $table->foreign('pelanggan_id')->references('id')->on('pelanggans')->onDelete('cascade');
    });
    }
};
