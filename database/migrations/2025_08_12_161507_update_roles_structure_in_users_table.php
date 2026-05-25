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
            // Ubah default role ke 3 (customer)
            $table->tinyInteger('role')->default(3)->change();
        });

        // Mapping data lama:
        // role 2 (customer lama) → 3 (customer baru)
        DB::table('users')->where('role', 2)->update(['role' => 3]);

        // role 1 (admin lama) → 2 (admin baru)
        DB::table('users')->where('role', 1)->update(['role' => 2]);

        // Tambah 1 superadmin default (kalau belum ada)
        if (!DB::table('users')->where('role', 1)->exists()) {
            DB::table('users')->insert([
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => bcrypt('superadmin123'), // ganti nanti
                'role' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Balikin default role ke 2 (customer lama)
            $table->tinyInteger('role')->default(2)->change();
        });

        // Balikin mapping role kalau di-rollback
        DB::table('users')->where('role', 3)->update(['role' => 2]);
        DB::table('users')->where('role', 2)->update(['role' => 1]);

        // Hapus superadmin default kalau ada
        DB::table('users')->where('email', 'superadmin@example.com')->delete();
    }
};
