<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Unit mobil tidak dihapus: dinonaktifkan agar riwayat pesanan, invoice, dan laporan tetap utuh. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobils', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('status_approval');
        });
    }

    public function down(): void
    {
        Schema::table('mobils', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }
};
