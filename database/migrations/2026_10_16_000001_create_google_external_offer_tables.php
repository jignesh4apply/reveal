<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Play External Offers Program (Android, EEA users).
 *
 * google_external_offer_links: one row per token received from the Android app
 *   (Stripe metadata only holds the short `ref`, because Stripe limits metadata values to 500 chars).
 * google_external_transactions: one row per payment / refund that must be reported to Google.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_external_offer_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('ref')->unique();
            $table->char('token_hash', 64)->unique();
            $table->text('token');
            $table->string('source', 32); // quiz | packages | subscription_details
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('google_external_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 16); // initial | renewal | refund
            $table->unsignedBigInteger('offer_link_id')->nullable();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable()->unique();
            $table->string('stripe_refund_id')->nullable()->unique();
            $table->unsignedBigInteger('refund_of_id')->nullable()->index();
            $table->boolean('is_full_refund')->default(false);
            $table->string('external_transaction_id', 63)->unique();
            $table->string('initial_external_transaction_id', 63)->nullable();
            $table->integer('gross_amount_cents');
            $table->bigInteger('pre_tax_micros');
            $table->bigInteger('tax_micros');
            $table->char('currency', 3);
            $table->char('region_code', 2)->nullable();
            $table->dateTime('transaction_time');
            $table->string('status', 20)->default('pending')->index(); // pending | sending | reported | failed | needs_attention
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->json('google_response')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_external_transactions');
        Schema::dropIfExists('google_external_offer_links');
    }
};
