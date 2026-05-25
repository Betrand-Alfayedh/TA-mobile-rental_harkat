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
            Schema::table('transaksis', function (Blueprint $table) {
                $table->integer('jaminan')
                    ->after('tanggal_kembali')
                    ->comment('1 = KTP/ID Card, 2 = Motor untuk warga Yogyakarta');

                $table->decimal('uang_muka', 10, 2)
                    ->after('jaminan')
                    ->nullable()
                    ->comment('50% dari total harga');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn(['jaminan', 'uang_muka']);
        });
    }
};
