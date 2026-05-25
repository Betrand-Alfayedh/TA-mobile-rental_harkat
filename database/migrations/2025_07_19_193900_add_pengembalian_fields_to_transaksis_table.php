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
            $table->tinyInteger('status_pengembalian')
                ->nullable()
                ->after('status'); // taruh setelah status utama

            $table->date('tanggal_pengembalian_aktual')
                ->nullable()
                ->after('tanggal_kembali'); // taruh setelah tanggal_kembali
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['status_pengembalian', 'tanggal_pengembalian_aktual']);
        });
    }
};
