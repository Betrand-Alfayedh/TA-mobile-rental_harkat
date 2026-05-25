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
        Schema::table('mobils', function (Blueprint $table) {
            $table->tinyInteger('status_approval')
                ->default(2) // 0=rejected, 1=approved, 2=pending
                ->after('status')->comment('0=rejected, 1=approved, 2=pending');
        });

        Schema::table('supirs', function (Blueprint $table) {
            $table->tinyInteger('status_approval')
                ->default(2)
                ->after('status')->comment('0=rejected, 1=approved, 2=pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobils', function (Blueprint $table) {
            $table->dropColumn('status_approval');
        });

        Schema::table('supirs', function (Blueprint $table) {
            $table->dropColumn('status_approval');
        });
    }
};
