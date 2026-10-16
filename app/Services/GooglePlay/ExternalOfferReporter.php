<?php

namespace App\Services\GooglePlay;

use App\Models\GoogleExternalOfferLink;
use App\Models\GoogleExternalTransaction;
use App\Models\User;
use App\Models\UsersSubscriptions;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Records Stripe payments/refunds that belong to a Google Play external offer and reports them
 * to Google (externaltransactions API).
 *
 * - First paid invoice of a linked subscription        -> "initial"  (with the token)
 * - First invoice after a plan change via the link     -> "initial"  (with the new token)
 * - Every later invoice of that subscription            -> "renewal"  (initialExternalTransactionId)
 * - Stripe refunds of any reported invoice             -> refund (full or partial)
 *
 * Subscriptions with no token (iOS, web, ads traffic) are ignored.
 */
class ExternalOfferReporter
{
    public function __construct(private GooglePlayExternalTransactionsClient $client)
    {
    }

    /* ---------------------------------------------------------------------
     | Recording (called from the Stripe webhook)
     * ------------------------------------------------------------------- */

    /**
     * @param \Stripe\Invoice|object $invoice  data.object of invoice.payment_succeeded / invoice.paid
     */
    public function recordPaidInvoice($invoice): void
    {
        if (empty($invoice->subscription) || empty($invoice->id)) {
            return;
        }

        if (GoogleExternalTransaction::where('stripe_invoice_id', $invoice->id)->exists()) {
            return; // already recorded (webhook retries, invoice.paid + invoice.payment_succeeded)
        }

        $stripe = $this->stripe();
        $subscription = $stripe->subscriptions->retrieve($invoice->subscription);
        $metadata = $subscription->metadata ?? null;

        $offerRef = $metadata[ExternalOfferLink::META_OFFER_REF] ?? null;
        $changeRef = $metadata[ExternalOfferLink::META_CHANGE_OFFER_REF] ?? null;

        $latestInitial = GoogleExternalTransaction::where('stripe_subscription_id', $subscription->id)
            ->where('type', GoogleExternalTransaction::TYPE_INITIAL)
            ->latest('id')
            ->first();

        $type = null;
        $link = null;

        $changeLink = $changeRef ? GoogleExternalOfferLink::where('ref', $changeRef)->first() : null;
        $changeAlreadyUsed = $changeLink && GoogleExternalTransaction::where('stripe_subscription_id', $subscription->id)
            ->where('offer_link_id', $changeLink->id)
            ->exists();

        if ($changeLink && !$changeAlreadyUsed) {
            // Plan changed after following the link: Google treats it as a new purchase with the new token.
            $type = GoogleExternalTransaction::TYPE_INITIAL;
            $link = $changeLink;
        } elseif (!$latestInitial) {
            $link = $offerRef ? GoogleExternalOfferLink::where('ref', $offerRef)->first() : null;
            if (!$link) {
                return; // not an Android external offer purchase
            }
            $type = GoogleExternalTransaction::TYPE_INITIAL;
        } else {
            $type = GoogleExternalTransaction::TYPE_RENEWAL;
        }

        $grossCents = (int) ($invoice->amount_paid ?? 0);
        $currency = strtoupper((string) ($invoice->currency ?? 'eur'));
        $userId = $this->resolveUserId($subscription);
        $regionCode = $this->resolveRegionCode($invoice, $subscription, $userId);
        [$preTaxMicros, $taxMicros] = $this->splitTax($grossCents, $invoice->tax ?? null, $regionCode);

        $paidAt = $invoice->status_transitions->paid_at ?? $invoice->created ?? time();

        $row = GoogleExternalTransaction::create([
            'user_id' => $userId,
            'type' => $type,
            'offer_link_id' => $link?->id,
            'stripe_subscription_id' => $subscription->id,
            'stripe_invoice_id' => $invoice->id,
            'external_transaction_id' => (string) Str::uuid(),
            'initial_external_transaction_id' => $type === GoogleExternalTransaction::TYPE_RENEWAL
                ? $latestInitial->external_transaction_id
                : null,
            'gross_amount_cents' => $grossCents,
            'pre_tax_micros' => $preTaxMicros,
            'tax_micros' => $taxMicros,
            'currency' => $currency,
            'region_code' => $regionCode,
            'transaction_time' => Carbon::createFromTimestampUTC((int) $paidAt),
            // When the country is unknown (e.g. free trial, address page not filled yet) the row stays
            // pending and the country is looked up again from users.country when sending.
            'status' => GoogleExternalTransaction::STATUS_PENDING,
        ]);

        Log::info('Google external offer: transaction recorded', [
            'id' => $row->id, 'type' => $type, 'invoice' => $invoice->id, 'subscription' => $subscription->id,
        ]);

        $this->send($row);
    }

    /**
     * @param \Stripe\Charge|\Stripe\Refund|object $object  data.object of charge.refunded / refund.updated
     */
    public function recordRefunds($object): void
    {
        $chargeId = ($object->object ?? null) === 'charge' ? ($object->id ?? null) : ($object->charge ?? null);
        if (empty($chargeId)) {
            return;
        }

        $stripe = $this->stripe();
        $charge = $stripe->charges->retrieve($chargeId, ['expand' => ['refunds']]);

        if (empty($charge->invoice)) {
            return;
        }

        $original = GoogleExternalTransaction::where('stripe_invoice_id', $charge->invoice)
            ->whereIn('type', [GoogleExternalTransaction::TYPE_INITIAL, GoogleExternalTransaction::TYPE_RENEWAL])
            ->first();

        if (!$original) {
            return; // not a reported external offer payment
        }

        $refunds = $charge->refunds->data ?? [];
        usort($refunds, fn ($a, $b) => ($a->created ?? 0) <=> ($b->created ?? 0));

        foreach ($refunds as $refund) {
            if (($refund->status ?? null) !== 'succeeded') {
                continue;
            }
            if (GoogleExternalTransaction::where('stripe_refund_id', $refund->id)->exists()) {
                continue;
            }

            $alreadyRefunded = (int) GoogleExternalTransaction::where('refund_of_id', $original->id)->sum('gross_amount_cents');
            $refundCents = (int) $refund->amount;
            $isFull = ($alreadyRefunded + $refundCents) >= $original->gross_amount_cents;

            $ratio = $original->gross_amount_cents > 0 ? $refundCents / $original->gross_amount_cents : 0;
            $preTaxMicros = (int) round($original->pre_tax_micros * $ratio);
            $taxMicros = (int) round($original->tax_micros * $ratio);

            $row = GoogleExternalTransaction::create([
                'user_id' => $original->user_id,
                'type' => GoogleExternalTransaction::TYPE_REFUND,
                'stripe_subscription_id' => $original->stripe_subscription_id,
                'stripe_refund_id' => $refund->id,
                'refund_of_id' => $original->id,
                'is_full_refund' => $isFull,
                // Google refunds address the ORIGINAL transaction; this id is only our own row key.
                'external_transaction_id' => (string) Str::uuid(),
                'gross_amount_cents' => $refundCents,
                'pre_tax_micros' => $preTaxMicros,
                'tax_micros' => $taxMicros,
                'currency' => $original->currency,
                'region_code' => $original->region_code,
                'transaction_time' => Carbon::createFromTimestampUTC((int) ($refund->created ?? time())),
                'status' => GoogleExternalTransaction::STATUS_PENDING,
            ]);

            Log::info('Google external offer: refund recorded', ['id' => $row->id, 'refund' => $refund->id, 'full' => $isFull]);

            $this->send($row);
        }
    }

    /* ---------------------------------------------------------------------
     | Sending (immediately after recording, and by the retry command)
     * ------------------------------------------------------------------- */

    public function sendPending(int $limit = 100): void
    {
        // A process that died while sending leaves its row in "sending": release it after 15 minutes.
        GoogleExternalTransaction::where('status', GoogleExternalTransaction::STATUS_SENDING)
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update(['status' => GoogleExternalTransaction::STATUS_FAILED, 'last_error' => 'Released after an interrupted send.']);

        $rows = GoogleExternalTransaction::whereIn('status', [
                GoogleExternalTransaction::STATUS_PENDING,
                GoogleExternalTransaction::STATUS_FAILED,
            ])
            ->where('attempts', '<', (int) config('google_play.max_attempts', 20))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($rows as $row) {
            $this->send($row);
        }

        if (config('google_play.enabled')) {
            $this->alertLateRows();
        }
    }

    public function send(GoogleExternalTransaction $row): void
    {
        if (!config('google_play.enabled')) {
            return; // staging / local: keep the row pending, never call Google
        }

        // Renewals and refunds need their original transaction reported first.
        $dependency = $this->dependencyStatus($row);
        if ($dependency === GoogleExternalTransaction::STATUS_NEEDS_ATTENTION) {
            GoogleExternalTransaction::where('id', $row->id)
                ->whereIn('status', [GoogleExternalTransaction::STATUS_PENDING, GoogleExternalTransaction::STATUS_FAILED])
                ->update([
                    'status' => GoogleExternalTransaction::STATUS_NEEDS_ATTENTION,
                    'last_error' => 'The original transaction needs manual attention, so this one cannot be reported yet.',
                ]);
            return;
        }
        if ($dependency !== GoogleExternalTransaction::STATUS_REPORTED) {
            return; // original still pending: it is sent first (rows are processed in id order)
        }

        // Claim the row so the webhook and the retry command never send it twice.
        $claimed = GoogleExternalTransaction::where('id', $row->id)
            ->whereIn('status', [GoogleExternalTransaction::STATUS_PENDING, GoogleExternalTransaction::STATUS_FAILED])
            ->update(['status' => GoogleExternalTransaction::STATUS_SENDING, 'updated_at' => now()]);
        if ($claimed === 0) {
            return;
        }
        $row->refresh();

        $row->attempts++;
        $maxAttempts = (int) config('google_play.max_attempts', 20);

        if (!$row->region_code && !$this->fillMissingRegion($row)) {
            $row->status = $row->attempts >= $maxAttempts
                ? GoogleExternalTransaction::STATUS_NEEDS_ATTENTION
                : GoogleExternalTransaction::STATUS_FAILED;
            $row->last_error = 'Customer country unknown: no Stripe billing/card country and users.country is empty or not an EEA country.';
            $row->save();
            return;
        }

        try {
            $response = $row->type === GoogleExternalTransaction::TYPE_REFUND
                ? $this->client->refundTransaction($row->refundOf->external_transaction_id, $this->refundBody($row))
                : $this->client->createTransaction($row->external_transaction_id, $this->transactionBody($row));

            $row->status = GoogleExternalTransaction::STATUS_REPORTED;
            $row->google_response = $response;
            $row->reported_at = now();
            $row->last_error = null;
        } catch (GooglePlayAlreadyReportedException $e) {
            $row->status = GoogleExternalTransaction::STATUS_REPORTED;
            $row->reported_at = $row->reported_at ?? now();
            $row->last_error = 'Already reported (409): ' . Str::limit($e->getMessage(), 500);
        } catch (Throwable $e) {
            $row->status = $row->attempts >= $maxAttempts
                ? GoogleExternalTransaction::STATUS_NEEDS_ATTENTION
                : GoogleExternalTransaction::STATUS_FAILED;
            $row->last_error = Str::limit($e->getMessage(), 2000);
            Log::error('Google external offer: report failed', ['id' => $row->id, 'error' => $e->getMessage()]);
        }

        $row->save();
    }

    private function transactionBody(GoogleExternalTransaction $row): array
    {
        $body = [
            'originalPreTaxAmount' => $this->price($row->pre_tax_micros, $row->currency),
            'originalTaxAmount' => $this->price($row->tax_micros, $row->currency),
            'transactionTime' => $row->transaction_time->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
            'userTaxAddress' => ['regionCode' => $row->region_code],
        ];

        if ($row->type === GoogleExternalTransaction::TYPE_INITIAL) {
            $body['recurringTransaction'] = [
                'externalTransactionToken' => $row->offerLink->token,
                'externalSubscription' => ['subscriptionType' => 'RECURRING'],
            ];
            $body['externalOfferDetails'] = ['linkType' => 'LINK_TO_DIGITAL_CONTENT_OFFER'];
        } else {
            $body['recurringTransaction'] = [
                'initialExternalTransactionId' => $row->initial_external_transaction_id,
                'externalSubscription' => ['subscriptionType' => 'RECURRING'],
            ];
        }

        return $body;
    }

    private function refundBody(GoogleExternalTransaction $row): array
    {
        $body = ['refundTime' => $row->transaction_time->copy()->utc()->format('Y-m-d\TH:i:s\Z')];

        if ($row->is_full_refund) {
            $body['fullRefund'] = (object) [];
        } else {
            $body['partialRefund'] = [
                'refundId' => $row->stripe_refund_id,
                'refundPreTaxAmount' => $this->price($row->pre_tax_micros, $row->currency),
            ];
        }

        return $body;
    }

    private function price(int $micros, string $currency): array
    {
        return ['priceMicros' => (string) $micros, 'currency' => $currency];
    }

    /** Status of the transaction this row depends on ("reported" when it has none). */
    private function dependencyStatus(GoogleExternalTransaction $row): ?string
    {
        if ($row->type === GoogleExternalTransaction::TYPE_REFUND) {
            return optional($row->refundOf)->status;
        }

        if ($row->type === GoogleExternalTransaction::TYPE_RENEWAL) {
            return GoogleExternalTransaction::where('external_transaction_id', $row->initial_external_transaction_id)
                ->value('status');
        }

        return GoogleExternalTransaction::STATUS_REPORTED;
    }

    private function alertLateRows(): void
    {
        $hours = (int) config('google_play.alert_after_hours', 20);

        $late = GoogleExternalTransaction::where('status', '!=', GoogleExternalTransaction::STATUS_REPORTED)
            ->where('transaction_time', '<', now()->subHours($hours))
            ->where('transaction_time', '>', now()->subDays(3))
            ->pluck('id');

        if ($late->isNotEmpty()) {
            Log::critical("Google external offer: transactions not reported after {$hours}h (24h limit)", ['ids' => $late->all()]);
        }
    }

    /* ---------------------------------------------------------------------
     | Helpers
     * ------------------------------------------------------------------- */

    private function stripe(): \Stripe\StripeClient
    {
        return new \Stripe\StripeClient(config('services.stripe.secret') ?: env('STRIPE_SECRET'));
    }

    private function resolveUserId($subscription): ?int
    {
        $userId = UsersSubscriptions::where('subscription_id', $subscription->id)->value('user_id');

        if (!$userId && !empty($subscription->metadata['user_id'] ?? null)) {
            $userId = (int) $subscription->metadata['user_id'];
        }

        return $userId ? (int) $userId : null;
    }

    /**
     * Customer country (ISO 3166-1 alpha-2) from the Stripe payment, then users.country as fallback.
     * Returns null when not found or not in the VAT table (row then needs manual attention).
     */
    private function resolveRegionCode($invoice, $subscription, ?int $userId): ?string
    {
        $candidates = [];

        try {
            if (!empty($invoice->charge)) {
                $charge = $this->stripe()->charges->retrieve($invoice->charge);
                $candidates[] = $charge->payment_method_details->card->country ?? null;
                $candidates[] = $charge->billing_details->address->country ?? null;
            }
        } catch (Throwable $e) {
            Log::warning('Google external offer: could not read charge country', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }

        try {
            // Free trial: no charge yet, use the saved payment method
            $paymentMethodId = $subscription->default_payment_method ?? null;
            if (is_object($paymentMethodId)) {
                $paymentMethodId = $paymentMethodId->id ?? null;
            }
            if (!empty($paymentMethodId)) {
                $paymentMethod = $this->stripe()->paymentMethods->retrieve($paymentMethodId);
                $candidates[] = $paymentMethod->card->country ?? null;
                $candidates[] = $paymentMethod->billing_details->address->country ?? null;
            }
        } catch (Throwable $e) {
            Log::warning('Google external offer: could not read payment method country', ['subscription' => $subscription->id ?? null, 'error' => $e->getMessage()]);
        }

        try {
            // Card flow: the card is the customer's default source / default payment method
            if (!empty($invoice->customer)) {
                $customer = $this->stripe()->customers->retrieve($invoice->customer, [
                    'expand' => ['default_source', 'invoice_settings.default_payment_method'],
                ]);
                $candidates[] = $customer->invoice_settings->default_payment_method->card->country ?? null;
                $candidates[] = $customer->default_source->country ?? null;
                $candidates[] = $customer->address->country ?? null;
            }
        } catch (Throwable $e) {
            Log::warning('Google external offer: could not read customer country', ['invoice' => $invoice->id, 'error' => $e->getMessage()]);
        }

        $candidates[] = $invoice->customer_address->country ?? null;

        if ($userId) {
            $candidates[] = $this->countryNameToCode(User::where('id', $userId)->value('country'));
        }

        $vatRates = config('google_play.vat_rates', []);
        foreach ($candidates as $code) {
            $code = strtoupper((string) $code);
            if ($code !== '' && array_key_exists($code, $vatRates)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Second chance for rows recorded before the user filled in the /address page.
     */
    private function fillMissingRegion(GoogleExternalTransaction $row): bool
    {
        if (!$row->user_id && $row->stripe_subscription_id) {
            $row->user_id = UsersSubscriptions::where('subscription_id', $row->stripe_subscription_id)->value('user_id');
        }
        if (!$row->user_id) {
            return false;
        }

        $code = strtoupper((string) $this->countryNameToCode(User::where('id', $row->user_id)->value('country')));
        if ($code === '' || !array_key_exists($code, config('google_play.vat_rates', []))) {
            return false;
        }

        [$preTaxMicros, $taxMicros] = $this->splitTax($row->gross_amount_cents, null, $code);
        $row->region_code = $code;
        $row->pre_tax_micros = $preTaxMicros;
        $row->tax_micros = $taxMicros;

        return true;
    }

    /** users.country stores the country name (e.g. "France") from public/webAssets/json/countries.json */
    private function countryNameToCode(?string $name): ?string
    {
        if (empty($name)) {
            return null;
        }

        $path = public_path('webAssets/json/countries.json');
        if (!File::exists($path)) {
            return null;
        }

        foreach (json_decode(File::get($path), true) ?? [] as $country) {
            if (strcasecmp($country['name']['common'] ?? '', $name) === 0) {
                return $country['cca2'] ?? null;
            }
        }

        return null;
    }

    /**
     * Prices are VAT-inclusive. Returns [preTaxMicros, taxMicros].
     */
    private function splitTax(int $grossCents, $stripeTaxCents, ?string $regionCode): array
    {
        $grossMicros = $grossCents * 10000;

        if ($stripeTaxCents !== null && (int) $stripeTaxCents > 0) {
            $taxMicros = (int) $stripeTaxCents * 10000;
            return [$grossMicros - $taxMicros, $taxMicros];
        }

        $fixed = config('google_play.vat_fixed_rate');
        $rate = ($fixed !== null && $fixed !== '')
            ? (float) $fixed
            : (float) (config('google_play.vat_rates')[$regionCode] ?? 0);

        $preTaxMicros = (int) round($grossMicros / (1 + $rate / 100));

        return [$preTaxMicros, $grossMicros - $preTaxMicros];
    }
}
