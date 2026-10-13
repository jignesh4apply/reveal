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
        Schema::table('user_morphologies', function (Blueprint $table) {
            $table->longText('raw_report_json')->nullable()->after('composition_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_morphologies', function (Blueprint $table) {
            $table->dropColumn('raw_report_json');
        });
    }
};
