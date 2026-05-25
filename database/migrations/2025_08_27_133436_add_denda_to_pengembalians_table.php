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
        Schema::table('pengembalians', function (Blueprint $table) {
            // rename kalau kolom denda lama memang boolean
            if (Schema::hasColumn('pengembalians', 'denda')) {
                $table->renameColumn('denda', 'denda_flag');
            } else {
                $table->tinyInteger('denda_flag')->default(0)->after('tanggal_kembali');
            }

            $table->integer('nominal_denda')->default(0)->after('denda_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengembalians', function (Blueprint $table) {
            if (Schema::hasColumn('pengembalians', 'denda_flag')) {
                $table->renameColumn('denda_flag', 'denda');
            }
            $table->dropColumn('nominal_denda');
        });
    }
};
