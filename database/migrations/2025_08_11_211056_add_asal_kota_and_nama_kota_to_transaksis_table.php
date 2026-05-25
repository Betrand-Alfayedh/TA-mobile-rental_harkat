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
            $table->integer('asal_kota')
                ->after('supir_id')
                ->comment('1 = Yogyakarta, 2 = Foreigner');
            $table->string('nama_kota', 50)
                ->after('asal_kota')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['asal_kota', 'nama_kota']);
        });
    }
};
