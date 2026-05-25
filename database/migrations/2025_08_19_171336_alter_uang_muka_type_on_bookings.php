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
            Schema::table('bookings', function (Blueprint $table) {
                $table->decimal('uang_muka', 15, 2)->change();
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::table('bookings', function (Blueprint $table) {
                $table->tinyInteger('uang_muka')->unsigned()->change();
            });
        }
    };
