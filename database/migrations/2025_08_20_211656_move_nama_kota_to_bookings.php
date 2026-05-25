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

        Schema::table('bookings', function (Blueprint $table) {
            // tambahin kolom setelah asal_kota
            $table->string('nama_kota')->after('asal_kota')->nullable();
        });

        Schema::table('booking_details', function (Blueprint $table) {
            // hapus kolom dari booking_details
            if (Schema::hasColumn('booking_details', 'nama_kota')) {
                $table->dropColumn('nama_kota');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_details', function (Blueprint $table) {
            $table->string('nama_kota')->after('asal_kota'); // atau sesuaikan posisi
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('nama_kota');
        });
    }
};
