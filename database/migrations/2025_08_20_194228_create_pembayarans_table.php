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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade'); // relasi ke bookings
            $table->date('tanggal_pembayaran');
            $table->decimal('jumlah', 12, 2);
            $table->tinyInteger('metode_pembayaran')->nullable(); // 1 = cash, 2 = transfer, 3=qris
            $table->tinyInteger('jenis')->default(1); // 1=uang_muka, 2= pelunasan
            $table->tinyInteger('status_pembayaran')->default(0); // 0 = belum bayar, 1 = sudah bayar, 2=pending
            $table->string('foto_bukti')->nullable(); // opsional: path bukti
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
