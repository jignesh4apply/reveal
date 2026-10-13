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
        Schema::table('sessions', function (Blueprint $table) {
            $table->boolean('is_new_exercise_flow')->default(false)->after('exercise');
        });

        Schema::table('session_exercises', function (Blueprint $table) {
            $table->string('exercise_form')->nullable()->after('session_exercise_id');
            $table->string('rest_period')->nullable()->after('exercise_form');
            $table->string('duration')->nullable()->after('rest_period');
            $table->string('repetition')->nullable()->after('duration');
            $table->string('repetition_range')->nullable()->after('repetition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_exercises', function (Blueprint $table) {
            $table->dropColumn(['exercise_form', 'rest_period', 'duration', 'repetition', 'repetition_range']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn('is_new_exercise_flow');
        });
    }
};
