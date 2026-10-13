<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Converts the boolean `have_points` column to a decimal `points` column
     * so the admin can assign fractional point values (e.g. 0.5, 0.35) per answer.
     */
    public function up(): void
    {
        Schema::table('behaviour_test_quiz_answers', function (Blueprint $table) {
            $table->decimal('points', 3, 2)->default(0)->after('answer');
        });

        // Migrate existing data: copy have_points values into the new points column
        DB::table('behaviour_test_quiz_answers')->update([
            'points' => DB::raw('have_points'),
        ]);

        Schema::table('behaviour_test_quiz_answers', function (Blueprint $table) {
            $table->dropColumn('have_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('behaviour_test_quiz_answers', function (Blueprint $table) {
            $table->tinyInteger('have_points')->default(0)->after('answer');
        });

        DB::table('behaviour_test_quiz_answers')->update([
            'have_points' => DB::raw('CASE WHEN points > 0 THEN 1 ELSE 0 END'),
        ]);

        Schema::table('behaviour_test_quiz_answers', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
