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
        Schema::create('food_planner_selected_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_planner_id')->constrained()->cascadeOnDelete();
            $table->json('selected_unit_json')->nullable();
            $table->timestamps();

            $table->unique('food_planner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_planner_selected_units');
    }
};
