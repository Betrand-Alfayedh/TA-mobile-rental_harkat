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
        Schema::table('booking_details', function (Blueprint $table) {
            $table->dateTime('tanggal_sewa')->change();
            $table->dateTime('tanggal_kembali')->change();
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dateTime('tanggal_pembayaran')->nullable()->change();
            $table->dateTime('jatuh_tempo')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_details', function (Blueprint $table) {
            $table->date('tanggal_sewa')->change();
            $table->date('tanggal_kembali')->change();
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->date('tanggal_pembayaran')->nullable()->change();
            $table->date('jatuh_tempo')->nullable()->change();
        });
    }
};
