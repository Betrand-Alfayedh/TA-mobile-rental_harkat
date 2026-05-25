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
        // Optional: hapus kolom pakai_supir di bookings
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('pakai_supir');
        });
        Schema::table('booking_details', function (Blueprint $table) {
            $table->tinyInteger('pakai_supir')->after('mobil_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_details', function (Blueprint $table) {
            //
        });
    }
};
