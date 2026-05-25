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
        Schema::table('supirs', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });

        Schema::table('mobils', function (Blueprint $table) {
            $table->dropColumn('gambar');
        });
        Schema::table('supirs_and_mobils', function (Blueprint $table) {
            Schema::table('supirs', function (Blueprint $table) {
                $table->string('gambar')->after('status')->nullable();
            });

            Schema::table('mobils', function (Blueprint $table) {
                $table->string('gambar')->after('status')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supirs', function (Blueprint $table) {
            $table->string('gambar')->nullable();
        });

        Schema::table('mobils', function (Blueprint $table) {
            $table->string('gambar')->nullable();
        });
        Schema::table('supirs_and_mobils', function (Blueprint $table) {
            Schema::table('supirs', function (Blueprint $table) {
                $table->dropColumn('gambar');
            });

            Schema::table('mobils', function (Blueprint $table) {
                $table->dropColumn('gambar');
            });
        });
    }
};
