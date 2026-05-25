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
        Schema::table('pelanggans', function (Blueprint $table) {
            // Drop foreign key constraint dulu
            $table->dropForeign(['user_id']);
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            // Drop kolom user_id
            $table->dropColumn('user_id');
        });

        Schema::table('pelanggans', function (Blueprint $table) {
            // Tambah kolom user_id di posisi baru
            $table->unsignedBigInteger('user_id')->after('id');

            // Tambah foreign key lagi
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pelanggans', function (Blueprint $table) {
            //
        });
    }
};
