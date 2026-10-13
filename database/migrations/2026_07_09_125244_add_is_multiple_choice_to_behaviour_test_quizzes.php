<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds an `is_multiple_choice` flag to the quizzes table so the admin
     * can mark a question as allowing multiple answer selections.
     */
    public function up(): void
    {
        Schema::table('behaviour_test_quizzes', function (Blueprint $table) {
            $table->tinyInteger('is_multiple_choice')->default(0)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('behaviour_test_quizzes', function (Blueprint $table) {
            $table->dropColumn('is_multiple_choice');
        });
    }
};
