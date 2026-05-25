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
        Schema::table('users', function (Blueprint $table) {
            // Mindahin kolom password ke setelah email
            $table->string('password')->after('email')->change();

            // Mindahin kolom role ke setelah alamat
            $table->integer('role')->after('alamat')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Balikin ke urutan lama (contoh: password setelah name, role setelah password)
            $table->string('password')->after('name')->change();
            $table->integer('role')->after('password')->change();
        });
    }
};
