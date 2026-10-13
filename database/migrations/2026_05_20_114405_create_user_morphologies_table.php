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
        Schema::create('user_morphologies', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('type')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->unique();

            // Personal Details
            $table->string('gender')->nullable();
            $table->integer('age')->nullable();

            // Body Measurements
            $table->float('height', 8, 2)->nullable(); // cm
            $table->float('weight', 8, 2)->nullable(); // kg
            $table->float('target_weight', 8, 2)->nullable();

            $table->float('waist', 8, 2)->nullable();
            $table->float('hips', 8, 2)->nullable();
            $table->float('neck', 8, 2)->nullable();

            $table->float('bicep', 8, 2)->nullable();
            $table->float('chest', 8, 2)->nullable();
            $table->float('thighs', 8, 2)->nullable();
            $table->float('calfs', 8, 2)->nullable();


            // Report Email
            $table->string('report_email')->nullable();

            // Pictures
            $table->string('image_fv')->nullable();
            $table->string('image_bv')->nullable();
            $table->string('image_ls')->nullable();
            $table->string('image_rs')->nullable();

            // AI / Report Status
            $table->enum('generation_status', [
                'pending',
                'processing',
                'completed',
                'failed'
            ])->default('pending');
            $table->text('generation_error')->nullable();

            // Calculated Metrics
            $table->decimal('bmi', 8, 2)->nullable();
            $table->decimal('whr', 8, 2)->nullable(); // Waist Hip Ratio
            $table->decimal('body_fat', 8, 2)->nullable();
            $table->decimal('whtr', 8, 2)->nullable();
            $table->decimal('lmi', 8, 2)->nullable();
            $table->decimal('composition_score', 8, 2)->nullable();

            // AI Generated Report
            $table->longText('report_json')->nullable();
            $table->date('report_generated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('generation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_morphologies');
    }
};
