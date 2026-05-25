<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('mobils', function (Blueprint $table) {
            // hapus kolom lama
            $table->dropColumn('nama_mobil');
            $table->dropForeign(['tipe_id']);
            $table->dropColumn('tipe_id');

            // tambahin relasi ke master_mobils
            $table->foreignId('master_mobil_id')
                ->after('id')
                ->constrained('master_mobils')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('mobils', function (Blueprint $table) {
            $table->string('nama_mobil')->after('id');
            $table->dropForeign(['master_mobil_id']);
            $table->dropColumn('master_mobil_id');
        });
    }
};
