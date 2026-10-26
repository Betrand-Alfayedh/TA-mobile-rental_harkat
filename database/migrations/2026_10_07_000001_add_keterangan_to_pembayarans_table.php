<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Kolom ini dipakai tagihan denda ("Denda keterlambatan") tetapi tidak pernah dibuatkan migrasinya. */
    public function up(): void
    {
        if (! Schema::hasColumn('pembayarans', 'keterangan')) {
            Schema::table('pembayarans', function (Blueprint $table) {
                $table->string('keterangan')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembayarans', 'keterangan')) {
            Schema::table('pembayarans', function (Blueprint $table) {
                $table->dropColumn('keterangan');
            });
        }
    }
};
