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
        Schema::create('user_morphology_behaviour_tests', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->longText('ques_ans_json')->nullable();
            $table->longText('report_json')->nullable();
            $table->enum('status', [
                'pending',
                'completed',
                'failed'
            ])->default('pending');
            $table->timestamps();
            $table->date('report_generated_at')->nullable();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_morphology_behaviour_tests');
    }
};
