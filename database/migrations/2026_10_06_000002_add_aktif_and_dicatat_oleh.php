<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('aktif')->default(true);
        });

        foreach (['pembayarans', 'pengembalians'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['pembayarans', 'pengembalians'] as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropConstrainedForeignId('dicatat_oleh');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }
};
