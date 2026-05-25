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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('pakai_supir'); // 0 g pakai supir, 1 pakai supir 
            $table->dateTime('tanggal_booking')->default(now());
            $table->tinyInteger('asal_kota'); // 0 bukan warlok, 1 = warlok(Yogyakarta)
            $table->tinyInteger('status')->default(1); // 0=canceled, 1=booked, 2=ongoing, 3=done
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
