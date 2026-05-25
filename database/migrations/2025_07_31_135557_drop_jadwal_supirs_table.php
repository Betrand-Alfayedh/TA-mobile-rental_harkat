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
        Schema::dropIfExists('jadwal_supirs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('jadwal_supirs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supir_id')->constrained('supirs')->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }
};
