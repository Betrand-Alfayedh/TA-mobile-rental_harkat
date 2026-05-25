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
        Schema::create('master_mobils', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // contoh: Avanza, Xenia, Pajero
            $table->foreignId('tipe_id')->constrained('tipe_mobils')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_mobils');
    }
};
