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
        Schema::table('promocodes', function (Blueprint $table) {
            $table->boolean('is_yearly_commitment')->default(0)->after('discount_type');
            $table->enum('yc_discount_type', ['percentage', 'amount'])->nullable()->after('is_yearly_commitment');
            $table->decimal('yc_discount_value', 8, 2)->nullable()->after('yc_discount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promocodes', function (Blueprint $table) {
            $table->dropColumn([
                'is_yearly_commitment',
                'yc_discount_type',
                'yc_discount_value'
            ]);
        });
    }
};
