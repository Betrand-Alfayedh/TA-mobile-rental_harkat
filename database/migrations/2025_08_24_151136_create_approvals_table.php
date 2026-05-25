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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // approvable_id, approvable_type
            $table->unsignedBigInteger('requested_by'); // admin
            $table->unsignedBigInteger('approved_by')->nullable(); // super admin
            $table->tinyInteger('status')->default(2)->comment('0 = Rejected, 1 = Approved, 2 = Pending');
            $table->text('note')->nullable(); // alasan reject
            $table->timestamps();

            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
