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
        Schema::create('behaviour_test_quizzes', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->unsignedBigInteger('behaviour_test_quiz_group_id')->index();
            $table->foreign('behaviour_test_quiz_group_id', 'fk_btq_group_id')
                ->references('id')
                ->on('behaviour_test_quiz_groups')
                ->cascadeOnDelete();
            $table->string('amplitude_word')->nullable();
            $table->string('slug');
            $table->integer('quiz_position')->default(0)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behaviour_test_quizzes');
    }
};
