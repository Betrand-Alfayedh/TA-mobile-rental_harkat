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
        // kalau job_offers udah ada → drop dulu
        Schema::dropIfExists('job_offers');

        // bikin ulang job_offers
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_detail_id');
            $table->unsignedBigInteger('supir_id');
            $table->timestamps();

            // FK ke booking_details
            $table->foreign('booking_detail_id')
                ->references('id')
                ->on('booking_details')
                ->onDelete('cascade');

            // FK ke supirs
            $table->foreign('supir_id')
                ->references('id')
                ->on('supirs')
                ->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
