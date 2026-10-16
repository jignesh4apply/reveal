<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PromoCodeUsage;
use App\Models\StripeCustomer;
use App\Models\SubscriptionHistories;
use App\Models\User;
use App\Models\UserSecurityToken;
use App\Models\UsersSubscriptions;
use App\Services\CustomerIoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\GooglePlay\ExternalOfferLink;

class NewSubscriptionController extends Controller
{
    public function packages(Request $request)
    {
        // Google Play external offer token from the Android app (?ext_token=...)
        ExternalOfferLink::captureFromRequest($request, 'packages');
        $getPackages = Plan::where('status', 1)->get();
        $user = Auth::guard('user')->user();
        if($user){
            return view('front.newSubscription.packages', ['allPlans' => $getPackages]);
        }
        $ValidUser = null;
        $data = $request->all();
        if(isset($data['uid']) && isset($data['st'])){
            $ValidUser = UserSecurityToken::where(['user_id' => $data['uid'], 'security_token' => $data['st']])->first();
        }
        if($ValidUser){
            session(['uid' => $data['uid'], 'st' => $data['st']]);
            $user = User::find($data['uid']);
            Auth::guard('user')->login($user);

            return view('front.newSubscription.packages', ['allPlans' => $getPackages]);
        }
        return redirect('user-login')->withErrors('Invalid User');
    }

    public function payment(Request $request)
    {
        if ($request->isMethod('post')) {
            try {
                $user = Auth::guard('user')->user();
                $getPackage = Plan::find($request->planId);

                if (!$getPackage) {
                    return back()->with('error', 'Invalid package selected.');
                }

                $userToken = UserSecurityToken::where('user_id', $user->id)->first();
                $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

                Log::info('Starting payment process', [
                    'user_id' => $user->id,
                    'payment_method' => $request->payment_method,
                    'plan_id' => $getPackage->id
                ]);

                /**
                 * --------------------------------------
                 * 1️⃣ Klarna Payment Handling
                 * --------------------------------------
                 */
                if ($request->payment_method === 'klarna') {
                    Log::info('Processing Klarna payment for plan', ['plan_id' => $getPackage->id]);

                    if ($getPackage->for_klarna == 0) {
                        return back()->with('error', 'Ce plan n\'est pas disponible pour Klarna.');
                    }

                    // Redirect to Klarna checkout session
                    return $this->redirectToKlarna($request);
                }

                /**
                 * --------------------------------------
                 * 2️⃣ Stripe Card Payment Handling
                 * --------------------------------------
                 */
                if (isset($request->stripeToken)) {
                    // Fetch or create Stripe Customer
                    $customerRecord = StripeCustomer::where('user_id', $user->id)->first();
                    $stripeCustomerId = $customerRecord ? json_decode($customerRecord->card_details)->id : null;

                    if (!$stripeCustomerId) {
                        $stripeCustomer = $this->createStripeCustomer($stripe, $user->email, $request->stripeToken);
                        $card = $this->getStripeCard($stripeCustomer->id, $stripeCustomer->default_source);
                        $this->saveStripeCustomer($user->id, $stripeCustomer, $card);
                    } else {
                        $stripeCustomer = $stripe->customers->retrieve($stripeCustomerId);
                        if (!empty($request->stripeToken)) {
                            // Attach new payment method
                            $newPaymentMethod = $stripe->paymentMethods->create([
                                'type' => 'card',
                                'card' => [
                                    'token' => $request->stripeToken,
                                ],
                            ]);
                            $newPaymentMethod->attach(['customer' => $stripeCustomerId]);
                            $stripeCustomer->invoice_settings->default_payment_method = $newPaymentMethod->id;
                            $stripeCustomer->save();
                            $card = $stripe->paymentMethods->retrieve($newPaymentMethod->id);
                            $this->saveStripeCustomer($user->id, $stripeCustomer, $card);
                        }
                    }

                    /**
                     * --------------------------------------
                     * 3️⃣ Handle Promocode (if any)
                     * --------------------------------------
                     */
                    $couponId = null;
                    if ((int)$getPackage->is_yearly_commitment === 1) {
                        $amount = (float) $getPackage->discprice ?? $getPackage->price;
                    } else {
                        $amount = (float) $getPackage->total_disc_price ?? $getPackage->total_price;
                    }

                    if ($request->promocode_discount != 0 && $request->promoCodeId != 0 && $request->promoCodeId != null) {
                        $discountAmount = abs(floatval($request->promocode_discount));

                        // =========================================================
                        // ✅ YEARLY COMMITMENT FLOW
                        // =========================================================
                        if ((int)$getPackage->is_yearly_commitment === 1 && (int)$request->is_yearly_commitment === 1) {

                            // Monthly price
                            $monthlyAmount = (float) $amount;

                            $percentOff = ($discountAmount / $monthlyAmount) * 100;

                            $coupon = $stripe->coupons->create([
                                'percent_off' => round($percentOff, 2),
                                'duration' => 'repeating',
                                'duration_in_months' => 12,
                            ]);

                            Log::info('Yearly commitment coupon created', [
                                'percent_off' => $percentOff,
                                'duration' => '12 months'
                            ]);

                        } else {

                            // =========================================================
                            // ✅ NORMAL FLOW (UNCHANGED)
                            // =========================================================
                            $percentOff = ($discountAmount / $amount) * 100;

                            $coupon = $stripe->coupons->create([
                                'percent_off' => round($percentOff, 2),
                                'duration' => 'once',
                            ]);

                            Log::info('Normal coupon created', [
                                'percent_off' => $percentOff
                            ]);
                        }

                        $couponId = $coupon->id;
                        PromoCodeUsage::create([
                            'user_id' => $user->id,
                            'promo_code_id' => $request->promoCodeId,
                            'applied_date' => now()->format('Y-m-d'),
                            'total_discount_price' => $discountAmount,
                        ]);
                    }

                    /**
                     * --------------------------------------
                     * 4️⃣ Create Plan + Subscription
                     * --------------------------------------
                     */
                    $plan = $this->createStripePlan($stripe, $getPackage, $amount);
                    $isTrial = $getPackage->freetrial;
                    $trialDays = $getPackage->trialdays;

                    $subscription = $this->createStripeSubscription(
                        $stripe,
                        $stripeCustomer->id,
                        $plan->id,
                        $isTrial,
                        $trialDays,
                        $couponId,
                        $user->id
                    );

                    $latestInvoice = $stripe->invoices->retrieve($subscription->latest_invoice, ['expand' => ['payment_intent']]);
                    $paymentIntent = $latestInvoice->payment_intent ?? null;

                    /**
                     * --------------------------------------
                     * 5️⃣ Handle 3D Secure if Required
                     * --------------------------------------
                     */
                    if ($paymentIntent && in_array($paymentIntent->status, ['requires_action', 'requires_payment_method'])) {
                        return redirect()->route('newSubscription.stripe.auth', [
                            'user_id' => $user->id,
                            'invoiceID' => $subscription->latest_invoice,
                            'plan_id' => $getPackage->id,
                            'payment_intent' => $paymentIntent->id,
                            'payment_intent_client_secret' => $paymentIntent->client_secret,
                        ])->with('message', '3D Secure authentication is required.');
                    }

                    /**
                     * --------------------------------------
                     * 6️⃣ Subscription Success
                     * --------------------------------------
                     */
                    if (in_array($subscription->status, ['active', 'trialing'])) {
                        $this->saveSubscriptionData($user->id, $subscription, $getPackage->id);

                        // Send transactional email
                        $customerIo = new CustomerIoService();
                        $customerIo->sendTransactionalEmail($user->email, '8', [
                            'name' => $user->name ?? 'Subscriber',
                            'subscriptionId' => $subscription->id,
                            'startDate' => date("Y-m-d", $subscription->current_period_start),
                            'planName' => $getPackage->name,
                            'billingDate' => now()->format('Y-m-d'),
                            'nextBillingDate' => date("Y-m-d", $subscription->current_period_end),
                            'totalAmountCharged' => $subscription->plan['amount'] / 100,
                        ]);

                        return redirect('/subscription-details?uid=' . $user->id . '&st=' . $userToken['security_token'])
                            ->with('success', 'Vous avez été abonné avec succès');
                    }

                    Log::error('Subscription failed', ['subscription_status' => $subscription->status]);
                    return back()->with('error', 'Subscription could not be activated. Please try again.');
                }

            } catch (Exception $e) {
                Log::error('Payment error', ['error' => $e->getMessage()]);
                return back()->with('error', $e->getMessage());
            }
        }

        return view('front.newSubscription.payment', ['id' => $request->id ?? null]);
    }

    public function redirectToKlarna(Request $request)
    {
        try {
            $user = Auth::guard('user')->user();

            if (!$user) {
                return back()->with('error', 'User not authenticated.');
            }

            $plan = Plan::find($request->planId);
            if (!$plan) {
                return back()->with('error', 'Invalid plan selected.');
            }

            Log::info('Initiating Klarna payment for existing user', [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
            ]);

            // Determine amount
            $amount = $plan->total_disc_price ?? $plan->total_price;
            if ($request->final_price) {
                $amount = $request->final_price;
            }

            $discountAmount = 0;
            $finalPrice = $amount;

            Log::info('Final price calculated for Klarna', ['final_price' => $finalPrice]);

            /**
             * --------------------------------------
             * Initialize Stripe + Customer
             * --------------------------------------
             */
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            Log::info('Stripe client initialized for RECURRING subscription');

            $customer = $this->getOrCreateStripeCustomer($user);

            Log::info('Stripe customer ready for Klarna', [
                'stripe_customer_id' => $customer->id,
                'user_id' => $user->id
            ]);

            /**
             * =====================================================
             * CREATE STRIPE PRICE FOR RECURRING BILLING
             * =====================================================
             * CRITICAL: Use Price API for subscriptions
             */
            $interval = 'month';
            $intervalCount = 1;

            if ($plan->plan_type == '0') {
                $intervalCount = 1; // Monthly
            } elseif ($plan->plan_type == '1') {
                $intervalCount = 3; // Quarterly
            } elseif ($plan->plan_type == '2') {
                $intervalCount = 12; // Yearly
            } elseif ($plan->plan_type == '3') {
                $intervalCount = 24; // 2 years
            } elseif ($plan->plan_type == '4') {
                $intervalCount = 6; // Semi-annual
            }

            Log::info('Creating Stripe Price for Klarna recurring', [
                'interval' => $interval,
                'interval_count' => $intervalCount,
                'amount' => $finalPrice
            ]);

            $price = $stripe->prices->create([
                'unit_amount' => intval($finalPrice * 100),
                'currency' => 'eur',
                'recurring' => [
                    'interval' => $interval,
                    'interval_count' => $intervalCount,
                ],
                'product_data' => [
                    'name' => $plan->name,
                    'metadata' => [
                        'package_id' => $plan->id,
                    ],
                ],
            ]);

            Log::info('Stripe Price created', ['price_id' => $price->id]);

            /**
             * =====================================================
             * CREATE SUBSCRIPTION (NOT PAYMENTINTENT!)
             * =====================================================
             * KEY: payment_behavior='default_incomplete'
             * This creates subscription in 'incomplete' status
             * and generates PaymentIntent for first payment
             */
            $subscriptionData = [
                'customer' => $customer->id,
                'items' => [['price' => $price->id]],
                'payment_behavior' => 'default_incomplete', // ← CRITICAL
                'payment_settings' => [
                    'payment_method_types' => ['klarna'],
                    'save_default_payment_method' => 'on_subscription', // ← Auto-saves PM
                ],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => array_merge([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_method' => 'klarna'
                ], ExternalOfferLink::subscriptionMetadata()), // Google Play external offer (Android)
            ];

            // Add trial if applicable
            if ($plan->freetrial == 1 && $plan->trialdays > 0) {
                $subscriptionData['trial_period_days'] = $plan->trialdays;
                Log::info('Adding trial period', ['trial_days' => $plan->trialdays]);
            }

            Log::info('Creating Klarna recurring subscription', ['user_id' => $user->id]);
            $subscription = $stripe->subscriptions->create($subscriptionData);

            Log::info('Klarna subscription created', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
            ]);

            /**
             * Get client_secret from PaymentIntent
             * This is what the frontend needs to confirm payment
             */
            $latestInvoice = $subscription->latest_invoice;
            $paymentIntent = $latestInvoice->payment_intent;

            if (!$paymentIntent) {
                throw new \Exception('PaymentIntent not found on first invoice');
            }

            $clientSecret = $paymentIntent->client_secret;

            Log::info('Client secret obtained', [
                'payment_intent_id' => $paymentIntent->id,
                'subscription_id' => $subscription->id
            ]);

            /**
             * --------------------------------------
             * Return Klarna Checkout View with SUBSCRIPTION data
             * --------------------------------------
             */
            return view('front.newSubscription.klarna.checkout', [
                'payment_intent_id' => $paymentIntent->id,
                'clientSecret' => $clientSecret,
                'subscription_id' => $subscription->id, // ← NEW: Pass subscription ID
                'plan' => $plan,
                'user' => $user,
                'finalAmount' => $finalPrice,
                'is_recurring' => true, // ← NEW: Flag for frontend
            ]);

        } catch (Exception $e) {
            Log::error('Klarna Redirect Failed', [
                'error' => $e->getMessage(),
                'user_id' => isset($user) ? $user->id : 'N/A',
                'plan_id' => isset($plan) ? $plan->id : 'N/A'
            ]);

            return back()->with('error', 'Klarna Checkout failed: ' . $e->getMessage());
        }
    }


    private function saveStripeCustomer($userId, $stripeCustomer, $card)
    {
        $customer = StripeCustomer::where('user_id', $userId)->first();

        if (!$customer) {
            $customer = new StripeCustomer();
            $customer->user_id = $userId;
        }

        $customer->card_details = json_encode($stripeCustomer);
        $customer->card = json_encode($card);
        $customer->save();
    }


    private function createStripeCustomer($stripe, $email, $token = null)
    {
        $data = [
            "name" => $email,
            "email" => $email,
        ];

        if ($token) {
            $data['source'] = $token;
        }

        return $stripe->customers->create($data);
    }

    private function getStripeCard($customerId, $defaultSource)
    {
        if (empty($defaultSource)) {
            return null; // No card to retrieve
        }
        $response = Http::withBasicAuth(env('STRIPE_SECRET'), '')
            ->get("https://api.stripe.com/v1/customers/{$customerId}/cards/{$defaultSource}");
        return json_decode($response);
    }

    private function createStripePlan($stripe, $package, $amount)
    {
        $interval = 'month';
        if ($package->plan_type == '0') {
            $interval_count = 1;
        } elseif ($package->plan_type == '1') {
            $interval_count = 3;
        } elseif ($package->plan_type == '2') {
            $interval_count = 12;
        } elseif ($package->plan_type == '3') {
            $interval_count = 24;
        } elseif ($package->plan_type == '4') {
            $interval_count = 6;
        }

        return $stripe->plans->create([
            "product" => ["name" => $package->name],
            "amount" => $amount * 100,
            "currency" => 'EUR',
            "interval" => $interval,
            "interval_count" => $interval_count,
            "metadata" => [
                "package_id" => $package->id,
                "package_name" => $package->name,
                "is_yearly_commitment" => isset($package->is_yearly_commitment) ? $package->is_yearly_commitment : 0
            ],
        ]);
    }

    private function createStripeSubscription($stripe, $customerId, $planId, $isTrial, $trialDays = null, $couponId = null, $userId = null)
    {
        try {
            $params = [
                "customer" => $customerId,
                "items" => [["plan" => $planId]],
            ];

            // Add trial period if applicable
            if ($isTrial == 1 && $trialDays > 0) {
                $params['trial_period_days'] = $trialDays;
            }
            // Add coupon if provided
            if ($couponId) {
                $params['coupon'] = $couponId;
            }
            // Link to the Google Play external offer token (Android), if the user came from the app link
            $externalOfferMetadata = ExternalOfferLink::subscriptionMetadata($userId);
            if (!empty($externalOfferMetadata)) {
                $params['metadata'] = $externalOfferMetadata;
            }

            // Log params for debugging
            \Log::info('Stripe Subscription Params:', $params);

            return $stripe->subscriptions->create($params);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log the error for debugging
            \Log::error('Stripe API Error: ' . $e->getMessage());
            throw new \Exception("There was an issue creating the subscription: " . $e->getMessage());
        } catch (\Exception $e) {
            // Log any general errors
            \Log::error('General Error: ' . $e->getMessage());
            throw new \Exception("An unexpected error occurred: " . $e->getMessage());
        }
    }

    private function getOrCreateStripeCustomer($user, $token = null)
    {
        $existing = StripeCustomer::where('user_id', $user->id)->first();

        if ($existing) {
            return json_decode($existing->card_details);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $customer = $this->createStripeCustomer($stripe, $user->email, $token);

        $card = $this->getStripeCard($customer->id, $customer->default_source);
        $this->saveStripeCustomer($user->id, $customer, $card);

        return $customer;
    }


    private function resolveInvoiceId($invoiceID1, $subscriptionDetail): ?string
    {
        if (!empty($invoiceID1)) {
            return is_object($invoiceID1) ? ($invoiceID1->id ?? null) : (string) $invoiceID1;
        }

        $latestInvoice = $subscriptionDetail->latest_invoice ?? null;

        if (is_string($latestInvoice)) {
            return $latestInvoice;
        }

        if (is_object($latestInvoice) && !empty($latestInvoice->id)) {
            return $latestInvoice->id;
        }

        if (is_array($latestInvoice) && !empty($latestInvoice['id'])) {
            return $latestInvoice['id'];
        }

        return null;
    }

    private function saveSubscriptionData($userId, $subscriptionDetail, $planId, $withKlarna = null, $invoiceID1 = null)
    {
        $startDate = date("Y-m-d", $subscriptionDetail->current_period_start);
        $endDate = date("Y-m-d", $subscriptionDetail->current_period_end);
        // $customer_id = $subscriptionDetail->customer;
        $customer_id = (is_object($subscriptionDetail->customer))?$subscriptionDetail->customer->id:$subscriptionDetail->customer;
        $subsId = $subscriptionDetail->id;
        $amount = $subscriptionDetail->plan['amount'] / 100;
        $billing_cycle = $subscriptionDetail->plan['interval_count'];
        $status = $subscriptionDetail->status;
        $invoice_id = $this->resolveInvoiceId($invoiceID1, $subscriptionDetail);

        $plan = $planId ? Plan::find($planId) : null;
        $isYearlyMonthly = $plan && ((int) ($plan->is_yearly_commitment ?? 0) === 1);

        $is_cancellation_locked = 0;
        $subscription_year_cycle = 0;
        $lockDate = $startDate;
        if ($isYearlyMonthly) {
            $subscription_year_cycle = 1;
            $is_cancellation_locked = 1;
            $lockDate = date('Y-m-d', strtotime('+11 months', strtotime($startDate)));
        }
        $payment_method_type = 'card';
        if($withKlarna == 'klarna'){
            $payment_method_type = 'klarna';
        }

        Log::info('Saving subscription data', [
            'user_id' => $userId,
            'customer_id' => $customer_id,
            'subscription_id' => $subsId,
            'invoice_id' => $invoice_id,
            'plan_id' => $planId,
        ]);

        // Check if a record already exists in UsersSubscriptions
        $subscription = UsersSubscriptions::where('user_id', $userId)
                                        ->where('subscription_id', $subsId)
                                        ->where('customer_id', $customer_id)
                                        ->first();

        if ($subscription) {
            // Update existing record in UsersSubscriptions
            $subscription->plan_id = $planId ?? null;
            $subscription->next_plan_id = null;
            $subscription->amount = $amount;
            $subscription->billing_cycle = $billing_cycle;
            $subscription->status = $status;
            $subscription->start_date = $startDate;
            $subscription->end_date = $endDate;
            $subscription->is_yearly_commitment = $isYearlyMonthly ?? 0;
            $subscription->is_cancellation_locked = $is_cancellation_locked;
            $subscription->subscription_year_cycle = $subscription_year_cycle;
            $subscription->lockDate = $lockDate;
            $subscription->payment_method_type = $payment_method_type;
            $subscription->default_payment_method_type = $payment_method_type;
            $subscription->invoice_id = $invoice_id;
            $subscription->is_refunded = 0;
            $subscription->save();

            Log::info('Updated existing subscription record', [
                'subscription_id' => $subsId,
                'user_id' => $userId,
                'invoice_id' => $invoice_id,
            ]);

        } else {
            // Delete all old records for this user before creating a new one
            UsersSubscriptions::where('user_id', $userId)->delete();

            // Create new record in UsersSubscriptions
            $subscription = new UsersSubscriptions;
            $subscription->user_id = $userId;
            $subscription->plan_id = $planId ?? null;
            $subscription->next_plan_id = null;
            $subscription->customer_id = $customer_id;
            $subscription->subscription_id = $subsId;
            $subscription->payment_method_type = $payment_method_type;
            $subscription->default_payment_method_type = $payment_method_type;
            $subscription->invoice_id = $invoice_id;
            $subscription->amount = $amount;
            $subscription->billing_cycle = $billing_cycle;
            $subscription->status = $status;
            $subscription->start_date = $startDate;
            $subscription->end_date = $endDate;
            $subscription->is_yearly_commitment = $isYearlyMonthly ?? 0;
            $subscription->is_cancellation_locked = $is_cancellation_locked;
            $subscription->subscription_year_cycle = $subscription_year_cycle;
            $subscription->lockDate = $lockDate;
            $subscription->is_refunded = 0;
            $subscription->save();

            Log::info('Created new subscription record', [
                'subscription_id' => $subsId,
                'user_id' => $userId,
                'invoice_id' => $invoice_id,
            ]);

        }

        if (empty($invoice_id)) {
            Log::warning('Subscription saved without invoice_id', [
                'user_id' => $userId,
                'customer_id' => $customer_id,
                'subscription_id' => $subsId,
            ]);
        }

        // Insert a new record in SubscriptionHistories
        $subscriptionHistory = new SubscriptionHistories;
        $subscriptionHistory->plan_id = $planId ?? null;
        $subscriptionHistory->user_id = $userId;
        $subscriptionHistory->customer_id = $customer_id;
        $subscriptionHistory->subscription_id = $subsId;
        $subscriptionHistory->payment_method_type = $payment_method_type;
        $subscriptionHistory->invoice_id = $invoice_id;
        $subscriptionHistory->amount = $amount;
        $subscriptionHistory->status = $status;
        $subscriptionHistory->start_date = $startDate;
        $subscriptionHistory->end_date = $endDate;
        $subscriptionHistory->taken_trial = 0;
        $subscriptionHistory->taken_discount = 0;
        $subscriptionHistory->is_yearly_commitment = $isYearlyMonthly ?? 0;
        $subscriptionHistory->is_cancellation_locked = $is_cancellation_locked;
        $subscriptionHistory->subscription_year_cycle = $subscription_year_cycle;
        $subscriptionHistory->lockDate = $lockDate;
        $subscriptionHistory->save();

        User::where('id', $userId)->update(['isSubscribedUser' => 1]);
    }

    public function newSubscriptionThreeDSuccessPayment(Request $request){
        Log::info('New Subscription 3D Secure Payment started.');

        try {
            // Decode and extract data from "u"
            $u = base64_decode($request->u);
            parse_str($u, $output);

            Log::info('Parsed 3DS output: ', $output);

            $invoiceId = $output['invoiceID'] ?? null;
            $user_id = $output['user_id'] ?? null;
            $plan_id = $output['plan_id'] ?? null;

            if (!$invoiceId || !$user_id || !$plan_id) {
                Log::error('Missing parameters in 3DS callback.', ['invoiceID' => $invoiceId, 'user_id' => $user_id, 'plan_id' => $plan_id]);
                return redirect()->route('packages')->with('error', 'Paramètres manquants. Veuillez réessayer.');
            }

            $getPackage = Plan::find($plan_id);
            $user = User::find($user_id);
            $userToken = UserSecurityToken::where('user_id', $user_id)->first();

            if (!$user || !$getPackage) {
                Log::error('User or Plan not found.', ['user' => $user, 'plan' => $getPackage]);
                return redirect()->route('packages')->with('error', 'Utilisateur ou plan introuvable.');
            }

            Log::info('Retrieved user and plan.', [
                'user_email' => $user->email,
                'plan_name' => $getPackage->name
            ]);

            // Initialize Stripe
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            Log::info('Stripe client initialized.');

            // Retrieve invoice and payment intent
            Log::info('Retrieving invoice from Stripe: ' . $invoiceId);
            $invoice = $stripe->invoices->retrieve($invoiceId, ['expand' => ['payment_intent']]);

            $subscriptionId = $invoice->subscription;
            $invoiceStatus = $invoice->status;
            $paymentIntentStatus = $invoice->payment_intent->status ?? null;

            Log::info('Stripe Invoice details', [
                'invoice_status' => $invoiceStatus,
                'payment_intent_status' => $paymentIntentStatus,
                'subscription_id' => $subscriptionId
            ]);

            // Success flow
            if ($invoiceStatus === 'paid') {
                Log::info('Invoice is paid. Retrieving subscription...');

                $subscription = $stripe->subscriptions->retrieve($subscriptionId, [
                    'expand' => ['customer', 'latest_invoice.payment_intent']
                ]);

                Log::info('Subscription retrieved successfully.', ['subscription_id' => $subscription->id]);

                $invoiceId = $invoice->id ?? $this->resolveInvoiceId($subscription->latest_invoice, $subscription) ?? null;

                // Save subscription data
                $this->saveSubscriptionData($user_id, $subscription, $getPackage->id, null, $invoiceId);
                Log::info('Subscription data saved.', [
                    'user_id' => $user_id,
                    'plan_id' => $getPackage->id,
                    'invoice_id' => $invoiceId,
                ]);

                // Notify via Customer.io (optional)
                $customerIo = new CustomerIoService();
                $customerIo->sendTransactionalEmail($user->email, '8', [
                    'planName' => $getPackage->name,
                    'totalAmountCharged' => $subscription->plan['amount'] / 100,
                ]);

                Log::info('Customer.io transactional email sent to: ' . $user->email);

                // Redirect to success page
                Log::info('Redirecting to subscription details.');
                return redirect('/subscription-details?uid=' . $user_id . '&st=' . $userToken['security_token'])
                    ->with('success', 'Votre abonnement a été activé avec succès.');
            }

            // If not paid
            Log::warning('Invoice not paid.', ['invoice_status' => $invoiceStatus]);
            return redirect()->route('packages')->with('error', 'Le paiement 3D Secure a échoué. Veuillez réessayer.');

        } catch (\Exception $e) {
            Log::error('Error in newSubscriptionThreeDSuccessPayment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('packages')->with('error', 'Erreur de paiement : ' . $e->getMessage());
        }
    }

    public function confirmKlarna(Request $request)
    {
        $paymentIntentId = $request->get('payment_intent');

        // Fetch the PaymentIntent from Stripe
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        try {
            // Retrieve PaymentIntent with invoice and expanded subscription
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId, [
                'expand' => ['invoice.subscription']
            ]);

            $status = $request->get('redirect_status');

            // Get metadata from the subscription object inside the invoice
            $subscription = $paymentIntent->invoice->subscription;
            $metadata = $subscription->metadata ?? null;

            // Extract user_id and plan_id from the subscription metadata
            $userId = $metadata->user_id ?? null;
            $planId = $metadata->plan_id ?? null;

            if (!$userId || !$planId) {
                return redirect()->route('packages')->with('error', 'Détails du paiement manquants.');
            }

            $userToken = UserSecurityToken::where('user_id', $userId)->first();

            if ($status === 'succeeded') {
                $this->finalizeNewKlarnaSubscription($paymentIntent, $userId, $planId, $subscription);
                return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])
                                            ->with('success', 'Vous avez été abonné avec succès');
            } else {
                return redirect()->route('user-subscription-package')->with('error', 'Le paiement Klarna a échoué. Veuillez réessayer.');
            }
        } catch (\Exception $e) {
            return redirect()->route('user-subscription-package')->with('error', 'Erreur Klarna : ' . $e->getMessage());
        }
    }

    private function finalizeNewKlarnaSubscription($paymentIntent, $userId, $planId, $subscription)
    {
        $user = User::find($userId);
        $plan = Plan::find($planId);

        if (!$user || !$plan) {
            Log::error('User or Plan missing for Klarna finalize');
            return;
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $withKlarna = 'klarna';

        /**
         * Retrieve the REAL subscription from Stripe
         * We created it in redirectToKlarna(), now we fetch it
         */
        if ($subscription) {

            Log::info('Real subscription retrieved', [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'customer_id' => $subscription->customer
            ]);

            // Save subscription data using the REAL subscription
            $this->saveSubscriptionData($userId, $subscription, $plan->id, $withKlarna);

            // Optional: notify user
            $customerIo = new CustomerIoService();
            $customerIo->sendTransactionalEmail($user->email, '8', [
                'planName' => $plan->name,
                'totalAmountCharged' => $subscription->plan['amount'] / 100,
            ]);

            Log::info("Klarna new subscription activated", [
                'user_id' => $user->id,
                'plan' => $plan->name,
                'subscription_id' => $subscription->id,
            ]);
        } else {
            Log::error('No subscription found in PaymentIntent', [
                'payment_intent_id' => $paymentIntent->id
            ]);
        }
    }
}
