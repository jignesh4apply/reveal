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
        Schema::create('behaviour_test_points_result_ranges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('behaviour_test_points_result_id')->index('bt_points_result_ranges_result_id_idx');
            $table->foreign('behaviour_test_points_result_id', 'fk_bt_points_result_ranges_result_id')
                ->references('id')
                ->on('behaviour_test_points_results')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('range_start');
            $table->unsignedTinyInteger('range_end');
            $table->string('result_text');
            $table->unsignedInteger('range_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behaviour_test_points_result_ranges');
    }
};
