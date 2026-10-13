<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\StripeCustomer;
use App\Models\UnsubscriptionFlowQuestion;
use App\Models\feedback_question;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserFeedback;
use App\Models\UserSecurityToken;
use App\Models\UsersSubscriptions;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class UnsubscriptionController extends Controller
{
    public function subscriptionDetails(Request $request)
    {
        $ValidUser = null;
        $data = $request->all();
        if(isset($data['uid']) && isset($data['st'])){
            $ValidUser = UserSecurityToken::where(['user_id' => $data['uid'], 'security_token' => $data['st']])->first();
        }
        if($ValidUser){
            session(['uid' => $data['uid'], 'st' => $data['st']]);
            $user = User::find($data['uid']);
            Auth::guard('user')->login($user);
            $userID = Auth::guard('user')->user()->id;
            $userEmail = User::where('id', $data['uid'])->pluck('email')->first();

            // subscription details fetching
            $paidWithKlarna = false;
            // cancelled subscription data check
            $userSubscriptionAlreadyCanceledData = null;
            // fetting active subscription data
            $userSubscriptionData = UsersSubscriptions::where('user_id', $userID)
                ->where(function ($q) {
                    $q->where('is_refunded', 0)
                    ->orWhere(function ($q2) {
                        $q2->where('is_refunded', 1)
                            ->where('status', '!=', 'canceled');
                    });
                })
                ->latest()
                ->first();
            // checking already canceled subscription
            if($userSubscriptionData){
                if($userSubscriptionData->status == "canceled"){
                    $userSubscriptionAlreadyCanceledData = $userSubscriptionData;
                }
            }

            // plan info fetching
            $currentPlanName = null;
            $nextPlanName = null;
            $CanceledSubscription = null;
             $currentPlanPrice = null;
            $paidPrice = null;
            $applyToExisting = null; // new line

            // NEW if condition with apply_to_existing logic
            if($userSubscriptionData){
                // fetching current plan data
                $currentPlanName = $userSubscriptionData->plan_id ? Plan::find($userSubscriptionData->plan_id)->name : null;
                // fetching next plan data (changed plan case)
                $nextPlanName = $userSubscriptionData->next_plan_id ? Plan::find($userSubscriptionData->next_plan_id)->name : null;
                // fetching canceled subscription info
                $CanceledSubscription = $userSubscriptionData->is_applied_for_cancel == 1 ? 1 : null;
                // fetching current plan price
                $currentPlanPrice = null;
                $paidPrice = $userSubscriptionData->amount ?? null;

                if ($userSubscriptionData->plan_id) {
                    $plan = Plan::find($userSubscriptionData->plan_id);
                    if ($plan) {
                        // Get apply_to_existing flag for view
                        $applyToExisting = $plan->apply_to_existing ?? null;

                        // If apply_to_existing is false (0, false, or null), show what user is actually paying
                        // Otherwise show current plan price
                        if ($plan->apply_to_existing == 0 || $plan->apply_to_existing === false || $plan->apply_to_existing === null) {
                            // Show old price (what user is actually paying) - Grandfathered
                            $currentPlanPrice = $userSubscriptionData->amount;
                        } else {
                            // Show new price (current plan price) - Applied to existing
                            $currentPlanPrice = $plan->total_disc_price ?? $plan->total_price;
                        }
                    }
                }
                // fallback to the amount user actually paid
                if (empty($currentPlanPrice) && isset($userSubscriptionData->amount)) {
                    $currentPlanPrice = $userSubscriptionData->amount;
                }

            }

            // fetching stripe card details
            $stripeCardDetail = StripeCustomer::where('user_id', $data['uid'])->pluck('card')->first();
            $stripeCustomerDetails = StripeCustomer::where('user_id', $data['uid'])->pluck('card_details')->first();
            $last4 = 'xxxx';
            $brand = 'visa';
            $cust_id = "00";
            // decoding card details
            if ($stripeCardDetail) {
                $cardDetails = json_decode($stripeCardDetail, true);
                $customerDetails = json_decode($stripeCustomerDetails, true);
                $last4 = $cardDetails['card']['last4'] ?? 'xxxx';
                $brand = $cardDetails['card']['brand'] ?? 'visa';
                $cust_id = $customerDetails['id'];
            }
            // checking paid with klarna
            if($userSubscriptionData){
                if (str_starts_with($userSubscriptionData->subscription_id, 'klarna_') || $userSubscriptionData->payment_method_type == 'klarna') {
                    $paidWithKlarna = true;
                }
            }

            return view('front.unsubscription.subscription_details', compact('userEmail', 'userSubscriptionData', 'last4', 'brand', 'cust_id', 'nextPlanName', 'currentPlanName','userSubscriptionAlreadyCanceledData', 'CanceledSubscription', 'paidWithKlarna'));
        }
        return redirect('user-login')->withErrors('Invalid User');
    }

    public function updateCard(Request $request, $custId = null)
    {
        try {
            // Check if the user is authenticated
            if (!Auth::guard('user')->check()) {
                return redirect()->route('user-login')->withErrors('Veuillez vous connecter d\'abord');
            }
            $user = Auth::guard('user')->user();
            if ($custId && !$this->isUserAuthorizedForCustomer($user, $custId)) {
                Auth::guard('user')->logout();
                return redirect()->route('user-login')->withErrors("L'identifiant du client et l'identifiant de l'utilisateur ne correspondent pas !");
            }
            if ($request->isMethod('post')) {
                $request->validate([
                    'stripeToken' => 'required',
                ]);

                Stripe::setApiKey(env('STRIPE_SECRET'));
                // fetching existing customer
                $customer = Customer::retrieve($custId);
                // creating new payment method
                $paymentMethod = PaymentMethod::create([
                    'type' => 'card',
                    'card' => [
                        'token' => $request->stripeToken,
                    ],
                ]);

                // attaching payment method to customer
                $paymentMethod->attach(['customer' => $customer->id]);
                // updating customer's default payment method
                $customer->invoice_settings = ['default_payment_method' => $paymentMethod->id];
                $customer->save();

                // updating card details in local DB
                $stripeCustomer = StripeCustomer::where('user_id', $user->id)->first();
                if ($stripeCustomer) {
                    $stripeCustomer->card_details = json_encode($customer);
                    $stripeCustomer->card = json_encode($paymentMethod);
                    $stripeCustomer->save();
                }

                $uid = session('uid');
                $st = session('st');
                session()->forget(['uid', 'st']);

                return redirect()->route('subscription-details', ['uid' => $uid, 'st' => $st])
                ->with('success', 'Les détails de la carte ont été mis à jour avec succès.');
            }
            $cust_id = $custId;
            return view('front.unsubscription.update_card', compact('cust_id'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Échec de la mise à jour de la carte :' . $e->getMessage());
        }
    }

    private function isUserAuthorizedForCustomer($user, $customerId)
    {
        $stripeCustomerDetails = StripeCustomer::where('user_id', $user->id)->pluck('card_details')->first();
        $customerDetails = json_decode($stripeCustomerDetails, true);
        $cust_id = $customerDetails['id'];
        return $cust_id === $customerId;
    }
    public function login(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $rules = [
                'email' => 'required|email',
                'password' => 'required'
            ];

            $messages = [
                'email.required' => 'Please enter your email',
                'email.email' => 'Please enter a valid email',
                'password.required' => 'Please enter your password'
            ];

            $validation = Validator::make($data, $rules, $messages);

            if ($validation->fails()) {
                return redirect()->back()->withInput()->withErrors($validation->messages());
            }

            if (Auth::guard('user')->attempt(['email' => $data['email'], 'password' => $data['password'], 'type' => 2])) {
                $request->session()->regenerate();
                return redirect('/feedback/1')->with('success', 'Vous vous êtes connecté avec succès');
            } else {
                return redirect()->back()->with('error', 'Identifiants invalides');
            }
        }

        return view('front.unsubscription.login');
    }

    public function showScreen($position)
    {
        $userId = Auth::guard('user')->id();
        if($userId == null){
            return abort(404);
        }
        $userSubscriptionDetail = UsersSubscriptions::where('user_id',$userId)->first();

        $screen = UnsubscriptionFlowQuestion::where('screen_position', $position)
            ->with('feedback')
            ->first();

        if (!$screen) {
            $firstScreen = UnsubscriptionFlowQuestion::with('feedback')
                        ->orderBy('screen_position', 'asc')
                        ->first();

            // Get the screen_position value
            $firstScreenPosition = $firstScreen ? $firstScreen->screen_position : null;

            return redirect()->route('feedback.show', $firstScreenPosition); // Redirect to the first screen if none found
        }
        if ($screen['is_multiple_button'] == 1){
            foreach($screen['multiple_buttons_value'] as $button){
                if($button['redirect_to'] == 2){
                    $screenButton = UnsubscriptionFlowQuestion::where('id',$button['redirect_screen_id'])->getColumns('screen_position');
                }
            }
        }
        return view('front.unsubscription.show', [
            'screen' => $screen,
            'userSubscriptionDetail' => $userSubscriptionDetail
        ]);
    }

    public function submitScreen(Request $request, $position)
    {

        $userId = Auth::guard('user')->id();
        if($userId == null){
            return abort(404);
        }
        $screen = UnsubscriptionFlowQuestion::where('screen_position', $position)->first();

        if ($screen->screen_type == 0) {

            if ($screen->feedback_type == "single"){
                $feedback_option_id = $request->feedback_id;
                $feedback = feedback_question::where('id', $feedback_option_id)->value('feedback_options');
            }elseif($screen->feedback_type == "multiple"){
                $feedback_option_id = $request->feedback_id;
                $feedbacks = [];
                foreach($feedback_option_id as $id){
                    $feedbacks[] = feedback_question::where('id', $id)->value('feedback_options');
                    $feedback = json_encode($feedbacks);
                }
            }elseif($screen->feedback_type == "userInput"){
                $feedback = $request->feedback;
            }
            $userFeedback = new UserFeedback();
            $userFeedback->user_id = $userId;
            $userFeedback->screen_id = $screen->id;
            $userFeedback->feedback = $feedback;
            $userFeedback->save();

        }

        $nextScreen = UnsubscriptionFlowQuestion::where('screen_position', '>', $position)
            ->orderBy('screen_position', 'asc')
            ->first();
        if ($nextScreen == null) {
            return redirect()->route('feedback.complete');
        } else {
            return redirect()->route('feedback.show', $nextScreen->screen_position);
        }
    }

    public function getScreenPositionById($screenId)
    {
        $screenPosition = UnsubscriptionFlowQuestion::where('id', $screenId)->value('screen_position');
        return response()->json(['screen_position' => $screenPosition]);
    }

    public function cancelSubscription(Request $request)
    {
        $userId = Auth::guard('user')->id();
        if($userId == null){
            return abort(404);
        }
        // fetching user subscription info
        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        // if no subscription found
        if (!$subscription) {
            return redirect()->back()->with('error', 'Aucun abonnement actif trouvé.');
        }
        // if already applied for cancel
        elseif($subscription['is_applied_for_cancel'] == 1){
            return redirect()->back()->with([
                'error' => 'Vous avez déjà demandé l\'annulation de votre abonnement, veuillez retourner à l\'application.',
                'hide_btn' => true
            ]);
        }

        // Check lock: if cancellation is locked and lock_cancel_until is in future, prevent cancellation
        if (isset($subscription->is_cancellation_locked) && $subscription->is_cancellation_locked == 1) {
            if (!empty($subscription->lockDate)) {
                $lockUntil = Carbon::parse($subscription->lockDate);
                if (Carbon::now()->lt($lockUntil)) {
                    return redirect()->back()->with('error', "Votre abonnement est verrouillé pour annulation jusqu'au {$lockUntil->toDateString()}.");
                }
            }
        }

        // stripe operations
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        try {
            // Retrieve the current subscription
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);
            // $currentSubscription = $stripe->subscriptions->retrieve("sub_1PbHNOJjlDk4jlc0WDk0ACMG");

            // Set the subscription to cancel at the end of the current period
            $canceledSubscription = $stripe->subscriptions->update($subscription->subscription_id, [
            // $canceledSubscription = $stripe->subscriptions->update("sub_1PbHNOJjlDk4jlc0WDk0ACMG", [
                'cancel_at_period_end' => true,
            ]);

            // Update local subscription status
            $subscription->is_applied_for_cancel = 1;
            $subscription->save();

            return redirect()->route('feedback.show', 20)->with([
                'success' => "La demande d'annulation de votre abonnement a été effectuée. Il restera actif jusqu'à la fin du cycle de facturation actuel et sera entièrement annulé par la suite.",
                'hide_back_btn' => true
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', "Échec de l'annulation de l'abonnement : " . $e->getMessage());
        }
    }

    public function discardCancelSubscription(Request $request)
    {
        $userId = Auth::guard('user')->id();

        if ($userId == null) {
            return abort(404);
        }

        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        if (!$subscription) {
            return redirect()->back()->with('error', 'Aucun abonnement actif trouvé.');
        } elseif ($subscription['is_applied_for_cancel'] == 0) {
            return redirect()->back()->with('error', "Aucune demande d'annulation trouvée à rejeter.");
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        try {
            // Retrieve the current subscription
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);

            // Check if the subscription is marked for cancellation
            if ($currentSubscription->cancel_at_period_end) {
                // Undo the cancellation request, effectively continuing the subscription
                $stripe->subscriptions->update($subscription->subscription_id, [
                    'cancel_at_period_end' => false, // Set to false to cancel the pending cancellation
                ]);

                // Update the local subscription status to reflect the cancellation was discarded
                $subscription->is_applied_for_cancel = 0; // Reset the cancellation flag
                $subscription->save();

                return redirect()->back()->with('success', "Votre demande d'annulation a été rejetée. Votre abonnement continuera.");
            }

            return redirect()->back()->with('error', "Aucune annulation n’est actuellement en cours.");
        } catch (Exception $e) {
            return redirect()->back()->with('error', "Échec de l'annulation de l'annulation :" . $e->getMessage());
        }
    }

    public function updateSubscription($per)
    {
        $userId = Auth::guard('user')->id();
        if($userId == null){
            return abort(404);
        }
        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        if (!$subscription) {
            return redirect()->back()->with('error', 'Aucun abonnement actif trouvé.');
        }elseif($subscription['is_applied_for_discount'] == 1){
            return redirect()->back()->with([
                'error' => 'Vous avez déjà bénéficié de la réduction, veuillez retourner à l\'application.',
                'hide_btn' => true
            ]);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        try {
            // Retrieve the current subscription
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);

            $discountCoupon = $stripe->coupons->create([
                'percent_off' => $per,
                'duration' => 'once',
            ]);

            $updatedSubscription = $stripe->subscriptions->update($subscription->subscription_id, [
                'coupon' => $discountCoupon->id,
                'proration_behavior' => 'none',
            ]);

            // Update local subscription status
            $subscription->is_applied_for_discount = 1;
            $subscription->save();

            return redirect()->route('feedback.show', 12)->with([
                'success' => 'Abonnement mis à jour avec succès.',
                'hide_back_btn' => true
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', "Échec de la mise à jour de l'abonnement : " . $e->getMessage());
        }
    }

    public function extendFreeTrial(Request $request)
    {
        $userId = Auth::guard('user')->id();
        if ($userId == null) {
            return abort(404);
        }
        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        if (!$subscription) {
            return redirect()->back()->with('error', 'Aucun abonnement actif trouvé.');
        } elseif ($subscription['is_applied_for_trial'] == 1) {
            return redirect()->back()->with([
                'error' => 'Vous avez déjà prolongé votre période d\'essai gratuite une fois, veuillez retourner à l\'application.',
                'hide_btn' => true
            ]);
        } elseif ($subscription['status'] != "trialing"){
            return redirect()->back()->with([
                'error' => 'Votre abonnement n\'est pas en période d\'essai, nous ne pouvons donc pas la prolonger. Veuillez retourner à l\'application.',
                'hide_btn' => true
            ]);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        try {
            // Retrieve the current subscription
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);

            // Calculate the new trial end date
            $newTrialEnd = $currentSubscription->trial_end + (7 * 24 * 60 * 60);

            // Update the subscription with the new trial end date
            $updatedSubscription = $stripe->subscriptions->update($subscription->subscription_id, [
                'trial_end' => $newTrialEnd,
                'proration_behavior' => 'none',
            ]);

            // Update local subscription status and end date
            $subscription->is_applied_for_trial = 1;
            $subscription->end_date = date('Y-m-d', $newTrialEnd);
            $subscription->save();

            return redirect()->route('feedback.show', 9)->with([
                'success' => 'La période d\'essai gratuite a été prolongée avec succès.',
                'hide_back_btn' => true
            ]);
        } catch (Exception $e) {
            return redirect()->back()->with('error', "Échec de la prolongation de la période d'essai gratuite : " . $e->getMessage());
        }
    }

    public function modifySubscriptionPlanPage()
    {
        $getPackages = Plan::where('status', 1)->get();
        $user_id = Auth::guard('user')->id();
        $userSubscriptionData = UsersSubscriptions::where('user_id', $user_id)->first();

        // Get current plan to check apply_to_existing flag (OPTION-2)
        $currentPlan = null;
        if ($userSubscriptionData && $userSubscriptionData->plan_id) {
            $currentPlan = Plan::find($userSubscriptionData->plan_id);
        }

        // Convert collection to array and add display prices (OPTION2)
        $plansArray = [];
        foreach ($getPackages as $plan) {
            $planData = $plan->toArray(); // Convert to array first

        // Check if this is user's current plan and should show old price
        $isCurrentPlan = $userSubscriptionData && $plan->id == $userSubscriptionData->plan_id;
        // Show old price if: current plan AND apply_to_existing is 0, false, or null (not applied to existing)
        $shouldShowOldPrice = $isCurrentPlan &&
                                  $currentPlan &&
                                  ($currentPlan->apply_to_existing == 0 ||
                                   $currentPlan->apply_to_existing === false ||
                                   $currentPlan->apply_to_existing === null);

            if ($shouldShowOldPrice) {
                // Show the price user is actually paying (from subscription) - OLD PRICE
                $planData['display_total_price'] = $userSubscriptionData->amount;
                $planData['display_total_disc_price'] = $userSubscriptionData->amount;
                // Calculate per month based on actual paid amount
                $planData['display_per_month_price'] = round(($userSubscriptionData->amount / 12), 2);
                $planData['display_per_month_disc_price'] = round(($userSubscriptionData->amount / 12), 2);
            } else {
                // Show new price from plan (either new plan or apply_to_existing is true) - NEW PRICE
                $planData['display_total_price'] = $plan->total_price;
                $planData['display_total_disc_price'] = $plan->total_disc_price;
                $planData['display_per_month_price'] = round(($plan->total_price / 12), 2);
                $planData['display_per_month_disc_price'] = round(($plan->total_disc_price / 12), 2);
            }

            $plansArray[] = $planData;
        }


        return view('front.unsubscription.modify_plan', [
            'user_id' => $user_id,
            'allPlans' => $plansArray,
            'subscriptionData' => $userSubscriptionData,
            // 'currentPlan' => $currentPlan
        ]);
    }

    public function modifySubscriptionPlan($plan_id)
    {
        $userId = Auth::guard('user')->id();
        if ($userId == null) {
            return abort(404);
        }
        $userToken = UserSecurityToken::where('user_id', $userId)->first();
        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        if (!$subscription) {
            return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])->with('error', 'Aucun abonnement actif trouvé.');
        }

        // Check lock: if cancellation is locked and lock_cancel_until is in future, prevent cancellation
        if (isset($subscription->is_cancellation_locked) && $subscription->is_cancellation_locked == 1) {
            if (!empty($subscription->lockDate)) {
                $lockUntil = Carbon::parse($subscription->lockDate);
                if (Carbon::now()->lt($lockUntil)) {
                    return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])->with('error', "Votre abonnement est verrouillé pour modification jusqu'au {$lockUntil->toDateString()}.");
                }
            }
        }

        try {
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);

            $newPlan = Plan::find($plan_id);
            if (!$newPlan) {
                return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])->with('error', 'Plan sélectionné non valide.');
            }

            // Get the current plan details
            $currentPlan = Plan::find($subscription->plan_id);

            // Check if plan type is changing (Klarna vs non-Klarna)
            $isNewPlanKlarna = $newPlan->for_klarna == 1;
            $isCurrentPlanKlarna = $currentPlan ? $currentPlan->for_klarna == 1 : false;

            // Get customer ID
            $customerId = $currentSubscription->customer;

            // Retrieve all payment methods for this customer
            $allPaymentMethods = $stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'card',
            ]);

            $klarnaPaymentMethods = $stripe->paymentMethods->all([
                'customer' => $customerId,
                'type' => 'klarna',
            ]);

            // Get current default payment method
            $customer = $stripe->customers->retrieve($customerId);
            $currentDefaultPaymentMethod = $customer->invoice_settings->default_payment_method;

            // Determine current payment method type
            $currentPaymentMethodType = null;
            if ($currentDefaultPaymentMethod) {
                $currentPM = $stripe->paymentMethods->retrieve($currentDefaultPaymentMethod);
                $currentPaymentMethodType = $currentPM->type; // 'card' or 'klarna'
            }

            // Handle payment method switching logic
            $needsPaymentMethodUpdate = false;
            $newDefaultPaymentMethod = null;

            // Scenario 1: Switching from non-Klarna plan to Klarna plan
            if (!$isCurrentPlanKlarna && $isNewPlanKlarna) {
                // Check if user has Klarna payment method
                if (count($klarnaPaymentMethods->data) > 0) {
                    // User has Klarna, set it as default
                    $newDefaultPaymentMethod = $klarnaPaymentMethods->data[0]->id;
                    $needsPaymentMethodUpdate = true;
                } else {
                    // User doesn't have Klarna, redirect to add Klarna payment method
                    session(['pending_plan_id' => $plan_id]);
                    return redirect()->route('add.klarna.payment', ['uid' => $userId, 'st' => $userToken['security_token']]);
                }
            }

            // Scenario 2: Switching from Klarna plan to non-Klarna plan
            elseif ($isCurrentPlanKlarna && !$isNewPlanKlarna) {
                // Check if user has card payment method
                if (count($allPaymentMethods->data) > 0) {
                    // User has card, set it as default
                    $newDefaultPaymentMethod = $allPaymentMethods->data[0]->id;
                    $needsPaymentMethodUpdate = true;
                } else {
                    // User doesn't have card, redirect to add card payment method
                    session(['pending_plan_id' => $plan_id]);
                    return redirect()->route('add.card.payment', ['uid' => $userId, 'st' => $userToken['security_token']]);
                }
            }

            // Scenario 3: Both plans are same type (Klarna to Klarna or non-Klarna to non-Klarna)
            else {
                // Check if appropriate payment method exists and switch if needed
                if ($isNewPlanKlarna) {
                    // Both are Klarna plans
                    if (count($klarnaPaymentMethods->data) > 0) {
                        if ($currentPaymentMethodType != 'klarna') {
                            $newDefaultPaymentMethod = $klarnaPaymentMethods->data[0]->id;
                            $needsPaymentMethodUpdate = true;
                        }
                    } else {
                        session(['pending_plan_id' => $plan_id]);
                        return redirect()->route('add.klarna.payment', ['uid' => $userId, 'st' => $userToken['security_token']]);
                    }
                } else {
                    // Both are non-Klarna plans
                    if (count($allPaymentMethods->data) > 0) {
                        if ($currentPaymentMethodType != 'card') {
                            $newDefaultPaymentMethod = $allPaymentMethods->data[0]->id;
                            $needsPaymentMethodUpdate = true;
                        }
                    } else {
                        session(['pending_plan_id' => $plan_id]);
                        return redirect()->route('add.card.payment', ['uid' => $userId, 'st' => $userToken['security_token']]);
                    }
                }
            }

            // Update default payment method if needed
            if ($needsPaymentMethodUpdate && $newDefaultPaymentMethod) {
                $stripe->customers->update($customerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $newDefaultPaymentMethod,
                    ],
                ]);

                // Determine the payment method type for the new payment method
                $paymentMethodType = $isNewPlanKlarna ? 'klarna' : 'card';

                // Update subscription's default payment method AND payment_settings
                // This is crucial to avoid the payment method type mismatch error
                $stripe->subscriptions->update($currentSubscription->id, [
                    'default_payment_method' => $newDefaultPaymentMethod,
                    'payment_settings' => [
                        'payment_method_types' => [$paymentMethodType],
                    ],
                ]);

                // Update local database with default payment method type
                $subscription->default_payment_method_type = $paymentMethodType;
            }

            if ((int)$newPlan->is_yearly_commitment === 1) {
                $newPlanAmount = (float) $newPlan->discprice ?? $newPlan->price;
            } else {
                $newPlanAmount = $newPlan->total_disc_price ?? $newPlan->total_price;
            }

            $currentSubscriptionEndDate = $currentSubscription->current_period_end;

            $interval = 'month';
            if ($newPlan->plan_type == '0') {
                $interval_count = 1;
            } elseif ($newPlan->plan_type == '1') {
                $interval_count = 3;
            } elseif ($newPlan->plan_type == '2') {
                $interval_count = 12;
            } elseif ($newPlan->plan_type == '3') {
                $interval_count = 24;
            } elseif ($newPlan->plan_type == '4') {
                $interval_count = 6; // Semi-annual
            }

            $stripePlan = $stripe->plans->create([
                'product' => ['name' => $newPlan->name],
                'amount' => $newPlanAmount * 100,
                'currency' => 'EUR',
                'interval' => $interval,
                'interval_count' => $interval_count,
                "metadata" => [
                    "package_id" => $newPlan->id,
                    "package_name" => $newPlan->name,
                    "is_yearly_commitment" => isset($newPlan->is_yearly_commitment) ? $newPlan->is_yearly_commitment : 0
                ],
            ]);

            // Determine payment method type for metadata
            $paymentMethodType = $isNewPlanKlarna ? 'klarna' : 'card';

            $updatedSubscription = $stripe->subscriptions->update($currentSubscription->id, [
                'items' => [
                    [
                        'id' => $currentSubscription->items->data[0]->id, // Subscription item ID
                        'plan' => $stripePlan->id, // New Stripe plan ID
                    ]
                ],
                'trial_end' => $currentSubscriptionEndDate, // Set the trial_end to the current subscription's end date
                'proration_behavior' => 'none',  // Prevent proration charges
                'metadata' => [
                    'user_id' => $userId,
                    'plan_id' => $newPlan->id,
                    'payment_method' => $paymentMethodType
                ],
            ]);

            // Update the local subscription data with the new plan details
            if ($newPlan->id != $subscription->plan_id) {
                $subscription->next_plan_id = $newPlan->id;
            } else {
                $subscription->next_plan_id = null;
            }
            $subscription->save();

            $successMessage = 'Votre abonnement a changé pour ' . $newPlan->name . '!';
            if ($needsPaymentMethodUpdate) {
                $successMessage .= ' Votre mode de paiement a été mis à jour.';
            }

            return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])->with('success', $successMessage);

        } catch (Exception $e) {
            return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])->with('error', "Échec de la mise à jour du plan d'abonnement : " . $e->getMessage());
        }
    }

    /**
     * Show add Klarna payment method page for plan modification
     */
    public function showAddKlarnaPayment(Request $request)
    {
        $userId = Auth::guard('user')->id();
        if ($userId == null) {
            return abort(404);
        }

        $uid = $request->uid;
        $st = $request->st;
        $pendingPlanId = session('pending_plan_id');

        if (!$pendingPlanId) {
            return redirect('/subscription-details?uid=' . $uid . '&st=' . $st)
                ->with('error', 'Aucun plan en attente trouvé.');
        }

        $plan = Plan::find($pendingPlanId);
        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        if (!$subscription) {
            return redirect('/subscription-details?uid=' . $uid . '&st=' . $st)
                ->with('error', 'Aucun abonnement actif trouvé.');
        }

        return view('front.unsubscription.add_klarna_payment', compact('uid', 'st', 'plan', 'subscription'));
    }

    /**
     * Process Klarna payment method addition and continue with plan modification
     */
    public function processAddKlarnaPayment(Request $request)
    {
        $userId = Auth::guard('user')->id();
        if ($userId == null) {
            return abort(404);
        }

        $pendingPlanId = session('pending_plan_id');
        if (!$pendingPlanId) {
            return redirect()->back()->with('error', 'Aucun plan en attente trouvé.');
        }

        try {
            $user = User::find($userId);
            $subscription = UsersSubscriptions::where('user_id', $userId)->first();
            $plan = Plan::find($pendingPlanId);

            if (!$subscription || !$plan) {
                return redirect()->back()->with('error', 'Abonnement ou plan introuvable.');
            }

            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);
            $customerId = $currentSubscription->customer;

            /**
             * For Klarna, we need to create a PaymentIntent with a minimal amount
             * to authorize the payment method. This will be refunded immediately.
             *
             * Klarna requires an actual transaction to set up the payment method,
             * unlike cards where we can use SetupIntent.
             */
            $setupAmount = 50; // 0.50 EUR - minimal authorization amount

            // Create PaymentIntent for Klarna authorization
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $setupAmount,
                'currency' => 'eur',
                'customer' => $customerId,
                'payment_method_types' => ['klarna'],
                'setup_future_usage' => 'off_session', // Save payment method for future use
                'metadata' => [
                    'type' => 'payment_method_setup',
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'pending_plan_modification' => $pendingPlanId,
                    'will_be_refunded' => 'true'
                ],
                'description' => 'Autorisation du mode de paiement Klarna (sera remboursé)',
            ]);

            $clientSecret = $paymentIntent->client_secret;

            // Store payment intent ID in session for refund after confirmation
            session(['klarna_setup_payment_intent_id' => $paymentIntent->id]);

            // Return Klarna checkout view
            return view('front.klarna.add_payment_checkout', [
                'payment_intent_id' => $paymentIntent->id,
                'clientSecret' => $clientSecret,
                'plan' => $plan,
                'user' => $user,
                'setupAmount' => $setupAmount / 100, // Convert to EUR for display
            ]);

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Échec de l\'ajout du mode de paiement Klarna : ' . $e->getMessage());
        }
    }

    /**
     * Confirm Klarna payment method addition and continue with plan modification
     */
    public function confirmAddKlarnaPayment(Request $request)
    {
        $paymentIntentId = $request->get('payment_intent');
        $userId = Auth::guard('user')->id();

        if (!$userId) {
            return abort(404);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        try {
            // Retrieve PaymentIntent
            $paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

            $status = $request->get('redirect_status');
            $metadata = $paymentIntent->metadata ?? null;

            $pendingPlanId = $metadata->pending_plan_modification ?? session('pending_plan_id');

            if ($status === 'succeeded') {
                // Payment method was successfully authorized
                $paymentMethodId = $paymentIntent->payment_method;

                if ($paymentMethodId) {
                    // Set Klarna as the default payment method
                    $stripe->customers->update($paymentIntent->customer, [
                        'invoice_settings' => [
                            'default_payment_method' => $paymentMethodId,
                        ],
                    ]);

                    // Update local database
                    $userSubscription = UsersSubscriptions::where('user_id', $userId)->first();
                    if ($userSubscription) {
                        $userSubscription->default_payment_method_type = 'klarna';
                        $userSubscription->save();
                    }

                    // Important: Refund the setup amount immediately
                    try {
                        // Get the charge from the PaymentIntent
                        if (isset($paymentIntent->charges->data[0])) {
                            $chargeId = $paymentIntent->charges->data[0]->id;

                            // Create refund
                            $refund = $stripe->refunds->create([
                                'charge' => $chargeId,
                                'reason' => 'requested_by_customer',
                                'metadata' => [
                                    'reason' => 'Payment method setup fee - automatically refunded'
                                ]
                            ]);

                            Log::info('Klarna setup fee refunded', [
                                'user_id' => $userId,
                                'charge_id' => $chargeId,
                                'refund_id' => $refund->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        // Log refund error but don't block the flow
                        Log::error('Failed to refund Klarna setup fee', [
                            'user_id' => $userId,
                            'payment_intent_id' => $paymentIntentId,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Clear session
                session()->forget(['pending_plan_id', 'klarna_setup_payment_intent_id']);

                return redirect()->route('modify.subscription.plan', ['plan_id' => $pendingPlanId])
                    ->with('success', 'Mode de paiement Klarna ajouté avec succès. Les frais d\'autorisation ont été remboursés.');

            } else {
                // Payment failed
                session()->forget(['pending_plan_id', 'klarna_setup_payment_intent_id']);

                $userToken = UserSecurityToken::where('user_id', $userId)->first();
                return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])
                    ->with('error', 'L\'autorisation Klarna a échoué. Veuillez réessayer.');
            }

        } catch (\Exception $e) {
            Log::error('Klarna confirmation error', ['error' => $e->getMessage()]);

            session()->forget(['pending_plan_id', 'klarna_setup_payment_intent_id']);

            $userToken = UserSecurityToken::where('user_id', $userId)->first();
            return redirect('/subscription-details?uid=' . $userId . '&st=' . $userToken['security_token'])
                ->with('error', 'Erreur Klarna : ' . $e->getMessage());
        }
    }

    /**
     * Show add card payment method page for plan modification
     */
    public function showAddCardPayment(Request $request)
    {
        $userId = Auth::guard('user')->id();
        if ($userId == null) {
            return abort(404);
        }

        $uid = $request->uid;
        $st = $request->st;
        $pendingPlanId = session('pending_plan_id');

        if (!$pendingPlanId) {
            return redirect('/subscription-details?uid=' . $uid . '&st=' . $st)
                ->with('error', 'Aucun plan en attente trouvé.');
        }

        $plan = Plan::find($pendingPlanId);
        $subscription = UsersSubscriptions::where('user_id', $userId)->first();

        if (!$subscription) {
            return redirect('/subscription-details?uid=' . $uid . '&st=' . $st)
                ->with('error', 'Aucun abonnement actif trouvé.');
        }

        $stripeCustomerDetails = StripeCustomer::where('user_id', $userId)->pluck('card_details')->first();
        $customerDetails = json_decode($stripeCustomerDetails, true);
        $cust_id = $customerDetails['id'] ?? null;

        return view('front.unsubscription.add_card_payment', compact('uid', 'st', 'plan', 'subscription', 'cust_id'));
    }

    /**
     * Process card payment method addition and continue with plan modification
     */
    public function processAddCardPayment(Request $request)
    {
        $userId = Auth::guard('user')->id();
        if ($userId == null) {
            return abort(404);
        }

        $pendingPlanId = session('pending_plan_id');
        if (!$pendingPlanId) {
            return redirect()->back()->with('error', 'Aucun plan en attente trouvé.');
        }

        try {
            $request->validate([
                'stripeToken' => 'required',
            ]);

            $subscription = UsersSubscriptions::where('user_id', $userId)->first();
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
            $currentSubscription = $stripe->subscriptions->retrieve($subscription->subscription_id);
            $customerId = $currentSubscription->customer;

            // Create payment method from token
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $customer = Customer::retrieve($customerId);
            $paymentMethod = PaymentMethod::create([
                'type' => 'card',
                'card' => [
                    'token' => $request->stripeToken,
                ],
            ]);

            $paymentMethod->attach(['customer' => $customer->id]);

            // Set as default payment method
            $customer->invoice_settings = ['default_payment_method' => $paymentMethod->id];
            $customer->save();

            // Update local database
            $stripeCustomer = StripeCustomer::where('user_id', $userId)->first();
            if ($stripeCustomer) {
                $stripeCustomer->card_details = json_encode($customer);
                $stripeCustomer->card = json_encode($paymentMethod);
                $stripeCustomer->save();
            }

            // Update subscription default payment method type
            $subscription->default_payment_method_type = 'card';
            $subscription->save();

            // Clear pending plan and redirect to modify plan
            session()->forget('pending_plan_id');

            return redirect()->route('modify.subscription.plan', ['plan_id' => $pendingPlanId])
                ->with('success', 'Carte ajoutée avec succès.');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Échec de l\'ajout de la carte : ' . $e->getMessage());
        }
    }

}

