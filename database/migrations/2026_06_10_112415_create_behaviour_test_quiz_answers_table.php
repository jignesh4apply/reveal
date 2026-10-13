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
        Schema::create('behaviour_test_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('behaviour_test_quiz_id')->index();
            $table->foreign('behaviour_test_quiz_id', 'fk_btq_answers_quiz_id')
                ->references('id')
                ->on('behaviour_test_quizzes')
                ->cascadeOnDelete();
            $table->string('answer');
            $table->tinyInteger('have_points')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behaviour_test_quiz_answers');
    }
};
