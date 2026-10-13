<x-paywall-layout title="Activate New Subscription">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        /* Layout + copy of your new styles (kept similar to package.blade) */
        .change-plan .section-title{
            text-align: center;
            margin-bottom: 30px;
        }
        .subscription-description.mt-12 { margin-top: 12px; }
        .change-plan .transition-img { text-align: center; margin-bottom: 8px; }
        .change-plan .transition-img img{ width: 160px; }

        .price-box-wrapper { max-width: 920px; margin: 0 auto; }
        .price-box { position: relative; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.05); margin-bottom: 12px; cursor: pointer; }
        .price-box.active { border-color: #1BC666; }
        .price-box .discount { position: absolute; left: 16px; top: -10px; background: #F3E8FF; padding: 6px 10px; border-radius: 8px; font-weight:700; font-size:12px; }
        .price-box .plan-select-icon { position: absolute; right: 16px; top: 50%; transform: translateY(-50%); display: none; }
        .price-box.active .plan-select-icon { display: block; }

        .price-box .price-left{ display:inline-block; vertical-align: middle; width:60%; }
        .price-box .price-right{ display:inline-block; vertical-align: middle; text-align:right; width:36%; }
        .price-box .plan-duration { margin:0 0 6px 0; font-size:16px; color: rgba(0,18,11,0.8); }
        .price-box .plan-discount { font-size:12px; color:#323D4B; }
        .price-box .main-amount { font-size:24px; margin:0; color:#00120B; }
        .price-box .per-month-amount { font-size:12px; color:#6B7280; }

        .accordion-content { display:none; padding-top:10px; color:#374151; font-size:14px; }
        .price-box.active .accordion-content { display:block; }

        .footer-button { position: sticky; bottom: 0; margin: auto; padding: 14px 16px; background: #fff; z-index: 50; }
        .btn-link { display:flex; align-items:center; justify-content:center; width:100%; padding:12px; border-radius:50px; background:#7662F1; color:#fff; font-weight:600; text-decoration:none; border:none; }

        /* Order summary styles (similar to package) */
        .order-summery { max-width: 920px; margin: 18px auto 60px; background:#fff; padding:16px; border-radius:12px; box-shadow: 0 6px 20px rgba(0,0,0,0.05); }
        .order-summery .title { margin:0 0 12px 0; font-size:18px; }
        .plan { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px dashed rgba(0,0,0,0.04); }
        .total { display:flex; justify-content:space-between; align-items:center; padding:10px 0; font-weight:700; font-size:18px; }

        .card-section { margin-top:14px; }
        .card-top { display:flex; gap:10px; align-items:center; margin-bottom:12px; }
        .card-img { height:28px; }

        /* Stripe element containers */
        .form-control { margin-bottom: 12px; border: 1px solid rgba(0,0,0,0.05); border-radius:8px; padding:10px; min-height:44px; }
        .cf-alerts { margin-bottom:12px; }

        /* small helpers */
        .loader { display:inline-block; width:18px; height:18px; border:3px solid #f3f3f3; border-top:3px solid #3498db; border-radius:50%; animation:spin 1s linear infinite; }
        @keyframes spin { 0%{transform:rotate(0)}100%{transform:rotate(360deg)} }

        /* Prevent this summary from inheriting any `.plan { display: flex }` rules */
        .yearly-commitment-info {
            display: none; /* hidden by default */
            padding: 10px;
            border: 1px dashed #cbd5e1;
            margin-bottom: 8px;
            box-sizing: border-box;
            flex: none !important;
            align-items: stretch !important;
            flex-direction: column !important;
        }

        /* If shown via JS (inline style or added class), force block layout */
        .yearly-commitment-info[style*="display:block"],
        .yearly-commitment-info.show {
            display: block !important;
        }
        .message {
            margin-top: 10px;
            font-size: 14px;
            padding: 12px 15px;
            border-radius: 6px;
            font-weight: 500;
            animation: slideIn 0.3s ease-in-out;
            display: none;
        }

        .message[style*="display"] {
            display: block !important;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    @endsection

    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <div class="main-header">
                    <div class="inner-header age-header">
                        <div class="header-top">
                            <div class="logo">
                                <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}" alt="logo">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plan Details (price boxes like package.blade) -->
                <div class="change-plan">
                    <div class="section-title">
                        <h2 class="title">Activer le nouveau plan.</h2>
                        <p class="subscription-description mt-12">Sélectionnez un plan puis payez — Klarna ou carte.</p>
                    </div>

                    <div class="transition-img">
                        <img src="{{ asset('webAssets/images/transition/transition-01.png') }}" alt="">
                    </div>

                    <div class="price-box-wrapper">
                        <div id="accordion" class="accordion-container">
                            @foreach($allPlans as $plan)
                                @php
                                    $perMonthOnPrice = round(($plan['total_price'] / 12), 2);
                                    $perMonthOnDiscPrice = round(($plan['total_disc_price'] / 12), 2);
                                    $priceDifference = ((float) $plan['total_price'] - (float) $plan['total_disc_price']);
                                    $dicsPercent = (int)(($priceDifference * 100) / (float) $plan['total_price']);
                                @endphp

                                <div class="price-box" data-package-id="{{ $plan['id'] }}" data-for-klarna="{{ $plan['for_klarna'] ?? 0 }}"  data-yearly-commitment="{{ $plan['is_yearly_commitment'] }}">
                                    @if(!empty($plan['offer_label']))
                                        <span class="discount">{{ $plan['offer_label'] }}</span>
                                    @endif

                                    <div class="plan-select-icon">
                                        <i class="fa-solid fa-check"></i>
                                    </div>

                                    <div class="accordion-title js-accordion-title">
                                        <div class="price-left">
                                            <h4 class="plan-duration">{{ $plan['name'] }}</h4>
                                            <div class="plan-discount">(-{{ $dicsPercent }}%) <p class="before-price" style="display:inline-block;margin-left:6px;">{{ $plan['total_price'] }} €</p> → <p class="discounted-price" style="display:inline-block;margin-left:6px;">{{ $plan['total_disc_price'] }} €</p></div>
                                        </div>

                                        @if(!empty($plan['for_klarna']) && $plan['for_klarna'] == 1)
                                            <img src="{{ asset('subscriptionDetails/images/klarna-img.png') }}" class="card-img" style="max-width: 48px; margin-right:8px;">
                                        @endif

                                        <div class="price-right">
                                            <h4 class="main-amount">{{ $plan['discprice'] }}</h4>
                                            <div class="per-month-amount">
                                                <p class="before-price">{{ $plan['price'] }}</p>
                                                <p class="per-month">€/mois</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="accordion-content">
                                        <p class="feature">{!! $plan['description'] !!}</p>
                                        {{-- NEW: Yearly-commitment vertical info (only visible for is_yearly_commitment == 1) --}}
                                        @if($plan['is_yearly_commitment'] == 1)
                                        <div class="yearly-commitment-note" style="margin-top:12px; padding:10px; border-left:4px solid #2b6cb0; background:#f7fbfe;">
                                            <strong>Engagement 12 mois — Paiement mensuel</strong>
                                            <p class="small">Vous payez <span class="yc-monthly">{{ $plan['discprice'] }}€</span> maintenant, puis <span class="yc-monthly">{{ $plan['discprice'] }}€</span> chaque mois pour 11 mois supplémentaires. Total : <strong class="yc-total">{{ $plan['total_disc_price'] }}€</strong>.</p>
                                            <p class="small">La résiliation n’est disponible que pendant le dernier mois de l’année d’abonnement.</p>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <!-- Plan Details -->

                <!-- Order Summary + Payment -->
                <section class="order-summery" id="order-summery">
                    <h4 class="title">Résumé de la commande</h4>
                    <div class="plan actual-plan">
                        <p class="plan-type"></p>
                        <p class="amount">0,00€</p>
                    </div>
                    {{-- Yearly Commitment Summary (hidden by default) --}}
                    <div class="yearly-commitment-info" style="display:none;">
                        <p><strong>Plan engagé annuel (paiement mensuel)</strong></p>
                        <p>Montant aujourd'hui : <span class="yc-pay-today">0€</span></p>
                        <p>Puis <span class="yc-monthly">0€</span> par mois pendant 11 mois</p>
                        <p>Total pour 12 mois : <span class="yc-total">0€</span></p>
                        <small class="yc-note">La résiliation n’est autorisée que pendant le dernier mois.</small>
                    </div>

                    <div class="plan discount-info" style="display:none;">
                        <p class="discount-percentage"></p>
                        <p class="discount-amount"><b>-0,00€</b></p>
                    </div>
                    <div class="plan klarna-fee" style="display:none;">
                        <p>frais de Klarna</p>
                        <p class="klarna-charge">0€</p>
                    </div>
                    <div class="promocode-applied" style="display:none;">
                        <p>Code promotionnel appliqué</p>
                        <p class="promocode-discount">0€</p>
                    </div>
                    <div class="yearly-commitment-promocode-applied" style="display: none;">
                        <p><strong>Code promotionnel appliqué - Plan annuel engagé</strong></p>
                        <div style="margin-top: 8px; padding: 10px; background: #f7fbfe; border-left: 3px solid #2b6cb0;">
                            <p>Prix/mois avant réduction : <strong class="yc-promo-monthly-before">0€</strong></p>
                            <p>Réduction/mois : <strong class="yc-promo-monthly-discount">0€</strong></p>
                            <p>Prix/mois après réduction : <strong class="yc-promo-monthly-after">0€</strong></p>
                            <p style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #cbd5e1;">
                                Total sur 12 mois : <strong class="yc-promo-total-yearly">0€</strong><br>
                                <span style="font-size: 13px; color: #666;">Total économisé : <strong class="yc-promo-total-savings">0€</strong></span>
                            </p>
                        </div>
                    </div>
                    <div class="total">
                        <p>Total à payer aujourd'hui</p>
                        <p class="total-amount">0,00 €</p>
                        <p class="promocode-id" style="display:none">0</p>
                    </div>
                    <p class="saved-amount" style="display:none;"></p>

                    <div class="card-section">
                        <div class="card-top">
                            <div class="card-img-wrapper klarna_img" style="display:none;">
                                <img src="{{ asset('subscriptionDetails/images/klarna-img.png') }}" class="card-img">
                            </div>
                            <div class="card-img-wrapper non_klarna_img">
                                <img class="visa" src="{{ asset('paywall/images/svgs/visa.svg') }}" alt="visa">
                                <img src="{{ asset('paywall/images/svgs/mastercard.svg') }}" alt="mastercard">
                                <img src="{{ asset('paywall/images/svgs/maestro.svg') }}" alt="maestro">
                                <img src="{{ asset('paywall/images/svgs/ae.svg') }}" alt="american express">
                            </div>
                            <img class="stripe" src="{{ asset('paywall/images/svgs/stripe.svg') }}" alt="stripe">
                        </div>

                        <div class="card-footer">
                            <!-- Payment form - submits to the route you provided -->
                            <form id="checkout-payment" class="add-card-form" action="{{ route('new-subscription-payment') }}" method="POST">
                                @csrf
                                <input type="hidden" id="selected-package-id" name="selected_package_id" value="">
                                <div class="cf-alerts"></div>

                                <!-- Stripe Elements -->
                                <div id="card_number" class="form-control"></div>
                                <div id="card_expiry" class="form-control"></div>
                                <div id="card_cvc" class="form-control"></div>

                                <!-- Promo code -->
                                <div class="discount-container" style="margin-top:10px;">
                                    <input type="text" id="discountCode" class="discount-input" placeholder="Ajouter un code promo">
                                    <button class="submit-button" id="checkCode" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M12 2L11.293 2.707 18.586 10H4v2h14.586l-7.293 7.293L12 22l10-10L12 2z" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="message" id="responseMessage"></div>

                                <button type="submit" id="nextButton" class="form-submit btn-link" style="margin-top:12px;">
                                    Activer le plan
                                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.2749 5.28264C13.6646 4.89131 14.2978 4.89 14.6891 5.27971L20.7261 11.2917C20.9145 11.4794 21.0205 11.7343 21.0205 12.0003C21.0205 12.2662 20.9145 12.5212 20.7261 12.7089L14.6891 18.7209C14.2978 19.1106 13.6646 19.1093 13.2749 18.7179C12.8852 18.3266 12.8865 17.6934 13.2778 17.3037L17.6192 12.9803H4.98047C4.42818 12.9803 3.98047 12.5326 3.98047 11.9803C3.98047 11.428 4.42818 10.9803 4.98047 10.9803H17.5791L13.2778 6.69685C12.8865 6.30714 12.8852 5.67398 13.2749 5.28264Z" fill="white" />
                                    </svg>
                                    <span id="loader" style="display:none;margin-left:8px;" class="loader"></span>
                                </button>

                                <button type="button" id="klarnaButton" class="form-submit btn-link" style="margin-top:10px; display:none; background:#7662f1;">
                                    <img src="{{ asset('subscriptionDetails/images/klarna-img.png') }}" class="card-img" style="margin-right:10px;">
                                    Payer avec Klarna
                                    <svg width="25" height="24" viewBox="0 0 25 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M13.2749 5.28264C13.6646 4.89131 14.2978 4.89 14.6891 5.27971L20.7261 11.2917C20.9145 11.4794 21.0205 11.7343 21.0205 12.0003C21.0205 12.2662 20.9145 12.5212 20.7261 12.7089L14.6891 18.7209C14.2978 19.1106 13.6646 19.1093 13.2749 18.7179C12.8852 18.3266 12.8865 17.6934 13.2778 17.3037L17.6192 12.9803H4.98047C4.42818 12.9803 3.98047 12.5326 3.98047 11.9803C3.98047 11.428 4.42818 10.9803 4.98047 10.9803H17.5791L13.2778 6.69685C12.8865 6.30714 12.8852 5.67398 13.2749 5.28264Z" fill="white" />
                                    </svg>
                                    <span id="loader1" style="display:none;margin-left:8px;" class="loader"></span>
                                </button>
                            </form>
                            <div class="refund-info" style="margin-top:12px;">
                                <div class="plan-info">
                                    <p>Reveal est un abonnement sans engagement. Tu peux résilier facilement à tout moment en quelques clics dans les paramètres. En validant cet abonnement, tu acceptes que ce montant soit facturé aujourd'hui. Ensuite, le même montant sera automatiquement prélevé à la même date, dans 12 mois ou dans 24 mois, selon l'offre que tu as choisie.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- <div class="footer-button">
                    <a href="#" class="btn-link" id="current-plan-btn">
                        <span id="plan-name">Veuillez sélectionner un plan.</span>
                    </a>
                </div> --}}
            </div>
        </div>
    </section>

    <div id="stripeKey" data-value="{{ config('services.stripe.key') }}"></div>

    @section('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            showMethod: 'slideDown',
            timeOut: 8000
        };

        @if(Session::has('success'))
        toastr.success("{{ session('success') }}", 'Success', {
            "background-color": "#4CAF50"
        });
        @endif

        @if(Session::has('error'))
        toastr.error("{{ session('error') }}", 'Error', {
            "background-color": "#f44336"
        });
        @endif
    </script>

    <script>
    $(document).ready(function() {
        // Helper: update order summary by calling same endpoint as package page (adjust backend if needed)
        function updateOrderSummary(selectedPackageId) {
            if (!selectedPackageId) return;
            $.ajax({
                url: "/package/details/" + selectedPackageId, // reuse existing endpoint
                type: "GET",
                success: function(response) {
                    if (response.success) {
                         const pkg = response.package;

                        // Base updates
                        $('.plan-type').text(pkg.name);
                        $('.amount').text((pkg.total_price ?? '') + "€");
                        $('.klarna-charge').text((pkg.klarnaCommision ?? 0) + "€");
                        $('.discount-percentage').text(pkg.discount_percentage ?? '');
                        $('.discount-amount b').text("-" + (pkg.discount_amount ?? 0) + "€");
                        $('.saved-amount').text(pkg.message ?? '');

                        // Promo / total
                        $('.total-amount').text((pkg.final_price ?? pkg.total_disc_price ?? pkg.total_price ?? 0) + "€");
                        $('.promocode-id').text(pkg.promocodeId ?? '');

                        // Yearly commitment specific
                        if (pkg.is_yearly_commitment == 1) {
                            $('.yearly-commitment-info').show();

                            // pay_today = the per-month amount (first month)
                            $('.yc-pay-today').text((pkg.monthly_price ?? pkg.discprice) + "€");
                            $('.yc-monthly').text((pkg.monthly_price ?? pkg.discprice) + "€");
                            $('.yc-total').text((pkg.total_disc_price ?? pkg.total_price) + "€");

                            // Update the main total to the immediate charge (pay today)
                            $('.total-amount').text((pkg.monthly_price ?? pkg.discprice) + "€");
                        } else {
                            $('.yearly-commitment-info').hide();

                            // For normal plans use final_price or total due today
                            $('.total-amount').text((pkg.final_price ?? pkg.total_price ?? 0) + "€");
                        }
                        $('.saved-amount').show();
                        $('.plan-type').show();
                        $('.order-summery .discount-info').show();
                        $('.total-amount').show();
                    } else {
                        console.error('Failed to fetch package details.');
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching package details:', xhr);
                }
            });
        }

        function updateUIForPackage(selectedPackageId) {
            const selectedPackage = $(`[data-package-id="${selectedPackageId}"]`);
            const forKlarna = selectedPackage.data('for-klarna');
            if (forKlarna == 1) {
                $('#klarnaButton, .klarna-fee, .klarna_img').show();
                $('#card_number, #card_expiry, #card_cvc, .discount-container, #nextButton, .non_klarna_img').hide();
            } else {
                $('#klarnaButton, .klarna-fee, .klarna_img').hide();
                $('#card_number, #card_expiry, #card_cvc, .discount-container, #nextButton, .non_klarna_img').show();
            }
        }

        // Stripe setup
        const stripeKey = $('#stripeKey').data('value');
        const stripe = Stripe(stripeKey);
        const elements = stripe.elements();
        const style = {
            base: {
                color: "#32325D",
                fontWeight: "500",
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSize: "16px",
                '::placeholder': { color: "#CFD7DF" }
            },
            invalid: { color: "#E25950", iconColor: "#E25950" }
        };
        const cardElement = elements.create('cardNumber', { style });
        const expElement = elements.create('cardExpiry', { style });
        const cvcElement = elements.create('cardCvc', { style });
        cardElement.mount('#card_number');
        expElement.mount('#card_expiry');
        cvcElement.mount('#card_cvc');

        // price-box click selection
        $('.price-box').on('click', function() {
            document.getElementById('discountCode').value = '';
            $('#responseMessage').text('').removeAttr('style').hide();
            $('.promocode-applied').hide();
            $('.yearly-commitment-promocode-applied').hide();
            $('.price-box').removeClass('active');
            $(this).addClass('active');
            var selectedPackageId = $(this).data('package-id');
            $('#selected-package-id').val(selectedPackageId);

            // update order summary and UI toggles
            updateOrderSummary(selectedPackageId);
            updateUIForPackage(selectedPackageId);
        });

        // select first by default (if exists)
        var firstPackageId = $('.price-box').first().data('package-id');
        if (firstPackageId) {
            $('.price-box').first().addClass('active');
            $('#selected-package-id').val(firstPackageId);
            updateOrderSummary(firstPackageId);
            updateUIForPackage(firstPackageId);
            var firstPlanName = $('.price-box').first().find('.plan-duration').text();
            // $('#current-plan-btn').html('Activer le plan <span id="plan-name">' + firstPlanName + '</span>');
        }

        // store selected package in session (reuse endpoint from package.blade)
        if (firstPackageId) {
            $.ajax({
                url: "/payment/" + firstPackageId,
                type: "GET",
                success: function(response) { if (!response.success) console.warn('Session store failed'); },
                error: function() { console.warn('Session store ajax error'); }
            });
        }

        // Form submission (Stripe flow)
        $('#checkout-payment').on('submit', async function(event) {
            event.preventDefault();
            $('#nextButton, #klarnaButton').prop('disabled', true);
            $('#loader, #loader1').show();

            var selectedPackageId = $('#selected-package-id').val();
            if (!selectedPackageId) {
                alert('Veuillez sélectionner un plan.');
                $('#nextButton, #klarnaButton').prop('disabled', false);
                $('#loader, #loader1').hide();
                return;
            }

            // ensure session saved (same as package flow)
            try {
                const storeResp = await $.ajax({ url: "/payment/" + selectedPackageId, type: "GET" });
                if (!storeResp.success) {
                    alert('Impossible de stocker le plan en session. Réessayez.');
                    $('#nextButton, #klarnaButton').prop('disabled', false);
                    $('#loader, #loader1').hide();
                    return;
                }
            } catch (e) {
                console.error(e);
                alert('Erreur réseau. Réessayez.');
                $('#nextButton, #klarnaButton').prop('disabled', false);
                $('#loader, #loader1').hide();
                return;
            }

            // Common hidden fields added
            var isYearlyCommitment = $('.yearly-commitment-promocode-applied').is(':visible');

            var promocodeDiscount = 0;
            var finalPrice = 0;

            if (isYearlyCommitment) {
                promocodeDiscount = $('.yc-promo-monthly-discount').text().replace('€', '').trim();
                finalPrice = $('.yc-promo-monthly-after').text().replace('€', '').trim();

                $('<input>').attr({
                    type: 'hidden',
                    name: 'is_yearly_commitment',
                    value: 1
                }).appendTo('#checkout-payment');

            } else {
                promocodeDiscount = $('.promocode-discount').text().replace('€', '').replace('-', '').trim() || 0;
                finalPrice = $('.total-amount').text().replace('€', '').trim() || 0;

                $('<input>').attr({
                    type: 'hidden',
                    name: 'is_yearly_commitment',
                    value: 0
                }).appendTo('#checkout-payment');
            }

            var promoCodeId = $('.promocode-id').text().trim() || '';

            // create hidden inputs
            $('<input>').attr({ type: 'hidden', name: 'planId', value: selectedPackageId }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'promoCodeId', value: promoCodeId }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'promocode_discount', value: promocodeDiscount }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'final_price', value: finalPrice }).appendTo('#checkout-payment');

            // by default assume Stripe flow (Klarna button sets payment_method to 'klarna')
            $('<input>').attr({ type: 'hidden', name: 'payment_method', value: 'card' }).appendTo('#checkout-payment');

            // Create token via Stripe
            const { token, error } = await stripe.createToken(cardElement);
            if (error) {
                // translation mapping as in your package file (shortened here to show pattern)
                let translatedMessage = error.message || 'Erreur carte. Réessayez.';
                $('.cf-alerts').html(`<div class="alert alert-danger"><p>${translatedMessage}</p></div>`);
                $('#nextButton, #klarnaButton').prop('disabled', false);
                $('#loader, #loader1').hide();
                return;
            } else {
                $('<input>').attr({ type: 'hidden', name: 'stripeToken', value: token.id }).appendTo('#checkout-payment');
                // submit to your route: route('new-subscription-payment', $id)
                $('#checkout-payment')[0].submit();
            }
        });

        // Klarna button behavior: set payment_method=klarna and submit (auto pop-up handled server-side)
        $('#klarnaButton').on('click', function () {
            // ensure selected package set
            var selectedPackageId = $('#selected-package-id').val();
            if (!selectedPackageId) {
                alert('Veuillez sélectionner un plan.');
                return;
            }

            // add hidden inputs like the submit handler
            var isYearlyCommitment = $('.yearly-commitment-promocode-applied').is(':visible');

            var promocodeDiscount = 0;
            var finalPrice = 0;

            if (isYearlyCommitment) {
                promocodeDiscount = $('.yc-promo-monthly-discount').text().replace('€', '').trim();
                finalPrice = $('.yc-promo-monthly-after').text().replace('€', '').trim();

                $('<input>').attr({
                    type: 'hidden',
                    name: 'is_yearly_commitment',
                    value: 1
                }).appendTo('#checkout-payment');

            } else {
                promocodeDiscount = $('.promocode-discount').text().replace('€', '').replace('-', '').trim() || 0;
                finalPrice = $('.total-amount').text().replace('€', '').trim() || 0;

                $('<input>').attr({
                    type: 'hidden',
                    name: 'is_yearly_commitment',
                    value: 0
                }).appendTo('#checkout-payment');
            }

            var promoCodeId = $('.promocode-id').text().trim() || '';

            // remove any previous hidden payment_method to avoid duplicates
            $('input[name="payment_method"]').remove();
            $('<input>').attr({ type: 'hidden', name: 'planId', value: selectedPackageId }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'promoCodeId', value: promoCodeId }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'promocode_discount', value: promocodeDiscount }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'final_price', value: finalPrice }).appendTo('#checkout-payment');
            $('<input>').attr({ type: 'hidden', name: 'payment_method', value: 'klarna' }).appendTo('#checkout-payment');

            $('#checkout-payment')[0].submit();
        });

        // promocode Script
        $('#checkCode').click(function(e) {
            e.preventDefault();
            var code = $('#discountCode').val();

            var selectedPackageId = $('#selected-package-id').val();

            if (code === "") {
                $('#responseMessage')
                    .text("Veuillez entrer un code de réduction.")
                    .css({
                        'color': '#721c24',
                        'background-color': '#f8d7da',
                        'border': '1px solid #f5c6cb'
                    })
                    .show();
                updateOrderSummary(selectedPackageId);
                updateUIForPackage(selectedPackageId);
                return;
            }

            // AJAX call to validate the code
            $.ajax({
                url: '/check-discount-code/' + (selectedPackageId ? selectedPackageId : ''), // Your backend endpoint
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    code: code
                },
                success: function(response) {
                    console.log(response);
                    if (response.isValid) {
                        // Check if this is a yearly commitment plan
                        if (response.isYearlyCommitment) {
                            // ================================================
                            // YEARLY COMMITMENT FLOW
                            // ================================================
                            $('.yearly-commitment-promocode-applied').show();
                            $('.promocode-applied').hide();

                            // Update yearly-specific fields
                            $('.yc-promo-monthly-before').text(response.promocode.monthly_price_before_discount + "€");
                            $('.yc-promo-monthly-discount').text(response.promocode.monthly_discount + "€");
                            $('.yc-promo-monthly-after').text(response.promocode.monthly_price_after_discount + "€");
                            $('.yc-promo-total-yearly').text(response.promocode.total_yearly_price + "€");
                            $('.yc-promo-total-savings').text(response.promocode.total_discount + "€");

                            // Update the total amount to pay today (first month)
                            $('.total-amount').text(response.promocode.monthly_price_after_discount + "€");

                            // Update saved message
                            $('.saved-amount').text(response.promocode.message);

                            // Store promo details
                            $('.promocode-id').text(response.promocode.promocodeId);

                            $('#responseMessage')
                                .text("✓ Code de réduction appliqué avec succès!")
                                .css({
                                    'color': '#155724',
                                    'background-color': '#d4edda',
                                    'border': '1px solid #c3e6cb'
                                })
                                .show();

                        } else {
                            // ================================================
                            // NORMAL PLANS FLOW
                            // ================================================
                            $('.promocode-applied').show();
                            $('.yearly-commitment-promocode-applied').hide();

                            // Update the DOM with the promocode details if valid
                            $('.promocode-discount').text("-" + response.promocode.promocode_discount + "€");
                            $('.total-amount').text(response.promocode.final_price + "€");
                            $('.saved-amount').text(response.promocode.message);
                            $('.promocode-id').text(response.promocode.promocodeId);

                            $('#responseMessage')
                                .text("✓ Code de réduction appliqué avec succès!")
                                .css({
                                    'color': '#155724',
                                    'background-color': '#d4edda',
                                    'border': '1px solid #c3e6cb'
                                })
                                .show();

                            // Optionally, display more promocode-related data
                            if (response.promocode.description) {
                                $('#promoDescription').text(response.promocode.description).show();
                            }
                        }
                    } else {
                        $('#responseMessage')
                            .text("Code de réduction invalide ou expiré. Veuillez vérifier et réessayer.")
                            .css({
                                'color': '#721c24',
                                'background-color': '#f8d7da',
                                'border': '1px solid #f5c6cb'
                            })
                            .show();
                        // Hide both promocode divs if the code is invalid
                        $('.promocode-applied').hide();
                        $('.yearly-commitment-promocode-applied').hide();
                        updateOrderSummary(selectedPackageId);
                        updateUIForPackage(selectedPackageId);
                    }
                },
                error: function(xhr) {
                    $('#responseMessage')
                        .text("Erreur lors de la vérification du code de réduction. Veuillez réessayer.")
                        .css({
                            'color': '#721c24',
                            'background-color': '#f8d7da',
                            'border': '1px solid #f5c6cb'
                        })
                        .show();
                    // Hide both promocode divs in case of an error
                    $('.promocode-applied').hide();
                    $('.yearly-commitment-promocode-applied').hide();
                    updateOrderSummary(selectedPackageId);
                    updateUIForPackage(selectedPackageId);
                }
            });
        });
        // End Promocode Script
    });
    </script>
    @endsection
</x-paywall-layout>