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
        Schema::dropIfExists('pembayarans');
    }

    public function down(): void
    {
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->onDelete('cascade');
            $table->date('tanggal_pembayaran');
            $table->decimal('jumlah', 12, 2);
            $table->tinyInteger('metode_pembayaran')->default(1);
            $table->string('foto_bukti')->nullable();
            $table->tinyInteger('status_pembayaran')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }
};
