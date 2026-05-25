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
        Schema::table('mobils', function (Blueprint $table) {
            $table->unsignedBigInteger('tipe_id')->nullable()->after('nama_mobil');
            $table->foreign('tipe_id')->references('id')->on('tipe_mobils')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobils', function (Blueprint $table) {
            $table->dropForeign(['tipe_id']);
            $table->dropColumn('tipe_id');
        });
    }
};
