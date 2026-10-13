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
        Schema::create('behaviour_test_quiz_answer_actions', function (Blueprint $table) {
            $table->id();
            
            // Shortened index name to prevent MySQL "identifier too long" error
            $table->unsignedBigInteger('behaviour_test_quiz_answer_id');
            $table->index('behaviour_test_quiz_answer_id', 'btq_ans_act_ans_id_idx');
            $table->foreign('behaviour_test_quiz_answer_id', 'fk_btq_ans_actions_ans_id')
                ->references('id')
                ->on('behaviour_test_quiz_answers')
                ->cascadeOnDelete();
                
            $table->unsignedBigInteger('behaviour_test_quiz_action_id');
            $table->index('behaviour_test_quiz_action_id', 'btq_ans_act_act_id_idx');
            $table->foreign('behaviour_test_quiz_action_id', 'fk_btq_ans_actions_action_id')
                ->references('id')
                ->on('quiz_actions')
                ->cascadeOnDelete();
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behaviour_test_quiz_answer_actions');
    }
};
