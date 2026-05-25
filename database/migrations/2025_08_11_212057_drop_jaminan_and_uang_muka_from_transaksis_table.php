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
            $table->dropColumn(['jaminan', 'uang_muka']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            $table->integer('jaminan')
                ->comment('1 = KTP/ID Card, 2 = Motor untuk warga Yogyakarta')
                ->nullable();

            $table->decimal('uang_muka', 12, 2)
                ->comment('50% dari total harga')
                ->nullable();
        });
    }
};
