<x-home-layout title="Change Subscription">
    @section('styles')
    <style>
        /* New CSS */
        .change-plan .section-title{
            text-align: center;
            margin-bottom: 50px;
        }

        .subscription-description.mt-12 {
            margin-top: 12px;
        }

        .change-plan .transition-img {
            text-align: center;
        }

        .change-plan .transition-img  img{
            width: 180px;
        }

        .change-plan-box {
            margin-top: -30px;
        }

        .change-plan-box .accordion {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .change-plan-box .accordion-item {
            position: relative;
            padding: 16px;
            background-color: #ffffff;
            border-radius: 16px;
            border: 2px solid rgba(0, 18, 11, 0.1);
            margin-bottom: 12px;
        }

        .change-plan-box .accordion-item:last-child {
            margin-bottom: 0;
        }

        .change-plan-box .accordion-item:has(.currunt-plan-tag.active) {
            padding-top: 35px;
        }

        .change-plan-box .accordion-header {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .change-plan-box .currunt-plan-tag {
            background-color: #B2B7B5;
            line-height: 1;
            padding: 6px;
            width: 100%;
            max-width: 254px;
            text-align: center;
            position: absolute;
            top: -2px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 0 0 16px 16px;
            display: none;
        }

        .change-plan-box .currunt-plan-tag.active {
            display: block;
        }

        .change-plan-box .currunt-plan-tag span {
            font-family: 'SFRounded';
            font-size: 13px;
            font-weight: 700;
            line-height: 16px;
            color: #ffffff;
        }

        .change-plan-box .plan-select-check {
            height: 20px;
            width: 20px;
            border-radius: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #1BC666;
            position: absolute;
            top: 50%;
            right: 0;
            transform: translate(50%, -50%);
            opacity: 0;
            visibility: hidden;
        }

        .change-plan-box .accordion-header .accordion-header-left .plan-duration {
            font-size: 16px;
            line-height: 19px;
            letter-spacing: -0.32px;
            margin: 0 0 2px 0;
            color: rgba(0, 18, 11, 0.6);
        }

        .change-plan-box .accordion-header .accordion-header-left .plan-discount {
            color: #323D4B;
            font-size: 12px;
            font-weight: 400;
            line-height: 14px;
        }

        .change-plan-box .accordion-header-right {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .change-plan-box .accordion-header-right .plan-main-amount {
            color: rgba(0, 18, 11, 0.6);
            font-size: 28px;
            line-height: 33px;
            margin: 0;
        }

        .change-plan-box .accordion-header-right .plan-before span {
            color: rgba(0, 18, 11, 0.6);
            font-size: 12px;
            font-weight: 500;
            line-height: 14px;
            display: block;
        }

        .change-plan-box .accordion-header-right .plan-before .plan-before-amount {
            margin-bottom: 1px;
            font-weight: normal;
        }

        .change-plan-box .accordion-body {
            display: none;
            margin-top: 12px;
        }

        .change-plan-box .accordion-body ul {
            list-style: disc;
            margin: 0;
            padding: 0 0 0 18px;
        }

        .change-plan-box .accordion-body ul li {
            margin-bottom: 5px;
            line-height: 1;
        }

        .change-plan-box .accordion-body ul li:last-child {
            margin-bottom: 0
        }

        .change-plan-box .accordion-body ul li span {
            font-size: 12px;
            font-weight: 400;
            line-height: 14px;
        }

        .change-plan-box .plan-feature-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .change-plan-box .plan-feature-list li {
            display: flex;
            gap: 6px;
            color: #00120B;
            font-size: 12px;
            line-height: 14px;
        }

        .change-plan-box .accordion-item:has(.accordion-body.show) {
            border-color: #1BC666;
        }

        .change-plan-box .accordion-item:has(.accordion-body.show) .currunt-plan-tag {
            background-color: #1BC666;
        }

        .change-plan-box .accordion-item:has(.accordion-body.show) .plan-select-check {
            opacity: 1;
            visibility: visible;
        }

        .change-plan-box .accordion-item:has(.accordion-body.show) .accordion-header-left .plan-duration,
        .change-plan-box .accordion-item:has(.accordion-body.show) .accordion-header-right .plan-main-amount,
        .change-plan-box .accordion-item:has(.accordion-body.show) .accordion-header-right .plan-before span {
            color: #00120B;
        }

        .change-plan .title {
            font-size: 24px;
            line-height: 28px;
            letter-spacing: -0.48px;
            margin: 0;
        }

        .footer-button {
            position: sticky ;
            margin-left: 50% !important;
            bottom: 0;
            margin: auto;
            border: none;
            font-weight: 600;
            padding: 30px 0;
            background-color: white;
        }

        .btn-link {
            font-family: 'SFRounded';
            font-size: 14px;
            line-height: 20px;
            font-weight: 600;
            text-align: center;
            padding: 12px;
            width: 100%;
            border-radius: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #7662F1;
            border-radius: 50px;
            box-shadow: none;
            border: none;
            color: #ffffff;
        }

        .btn-link.style-2 {
            padding: 6px 12px;
            background-color: #ffffff;
            color: #000000;
            margin-top: 16px;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0px 12px 24px -8px rgba(0, 0, 0, 0.1);
        }
        #plan-name {
            margin-left: 5px;
        }
        .card-img { height:28px; }
    </style>

    @endsection
    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <!-- Header -->
                <div class="main-header">
                    <div class="inner-header age-header">
                        <div class="header-top">
                            <div class="logo">
                                <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}" alt="logo">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NEW Plan Details -->
                <div class="change-plan">
                    <div class="section-title">
                        <h2 class="title">Changer mon forfait</h2>
                        <p class="subscription-description ques_description mt-12" id="new-start-date">Votre nouveau plan débutera le {{ \Carbon\Carbon::parse($subscriptionData['end_date'])->locale('fr')->isoFormat('MMM DD, YYYY') }}</p>
                    </div>

                    <div class="transition-img">
                        <img class="logo" src="{{ asset('webAssets/images/transition/transition-01.png') }}" alt="">
                    </div>
                    <div class="change-plan-box">
                        <ul class="accordion">
                            {{-- @dd($subscriptionData['plan_id']) --}}
                            @foreach ($allPlans as $plan)
                                <?php
                                    // new lines start here
                                    $displayTotalPrice = isset($plan['display_total_price']) ? $plan['display_total_price'] : $plan['total_price'];
                                    $displayTotalDiscPrice = isset($plan['display_total_disc_price']) ? $plan['display_total_disc_price'] : $plan['total_disc_price'];
                                    $perMonthOnPrice = isset($plan['display_per_month_price']) ? $plan['display_per_month_price'] : round(($plan['total_price'] / 12), 2);
                                    $perMonthOnDiscPrice = isset($plan['display_per_month_disc_price']) ? $plan['display_per_month_disc_price'] : round(($plan['total_disc_price'] / 12), 2);
                                    // new lines Ends here
                                ?>
                                <li class="accordion-item" data-plan-id="{{ $plan['id'] }}">
                                    <div class="accordion-header" href=#>
                                        @if ($subscriptionData['plan_id'] == $plan['id'])
                                            <div class="currunt-plan-tag active">
                                                <span>Plan actuel</span>
                                            </div>
                                        @endif
                                        <div class="plan-select-check">
                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M11.8128 3.27253C12.0772 3.53695 12.0772 3.96565 11.8128 4.23007L5.85442 10.1884C5.59 10.4528 5.1613 10.4528 4.89688 10.1884L2.18855 7.48007C1.92413 7.21565 1.92413 6.78695 2.18855 6.52253C2.45296 6.25811 2.88167 6.25811 3.14609 6.52253L5.37565 8.7521L10.8552 3.27253C11.1196 3.00811 11.5483 3.00811 11.8128 3.27253Z" fill="white"/>
                                            </svg>
                                        </div>
                                        <div class="accordion-header-left">
                                            <h5 class="plan-duration">{{$plan['name']}}</h5>
                                            <div class="plan-discount">
                                                {{-- new lines start here --}}
                                                @if($displayTotalPrice != $displayTotalDiscPrice)
                                                    <del>{{$displayTotalPrice}}€</del> → {{$displayTotalDiscPrice}}€
                                                @else
                                                    {{$displayTotalDiscPrice}}€
                                                @endif
                                                {{-- new lines Ends here --}}
                                            </div>
                                        </div>
                                        <div class="accordion-header-right">
                                            <h3 class="plan-main-amount">{{$perMonthOnDiscPrice}}</h3>
                                            <div class="plan-before">
                                                {{-- new lines start here --}}
                                                @if($perMonthOnPrice != $perMonthOnDiscPrice)
                                                    <span class="plan-before-amount">{{$perMonthOnPrice}}€</span>
                                                @endif
                                                {{-- new lines Ends here --}}
                                                <span>€/mois</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="accordion-body @if($subscriptionData['plan_id'] == $plan['id']) show" style="display: block; @endif">
                                        {!! $plan['description'] !!}
                                        {{-- NEW: Yearly-commitment vertical info (only visible for is_yearly_commitment == 1) --}}
                                        @if($plan['is_yearly_commitment'] == 1)
                                        <div class="yearly-commitment-note" style="margin-top:12px; padding:10px; border-left:4px solid #2b6cb0; background:#f7fbfe;">
                                            <strong>Engagement 12 mois — Paiement mensuel</strong>
                                            <p class="small">Vous payez <span class="yc-monthly">{{ $plan['discprice'] }}€</span> maintenant, puis <span class="yc-monthly">{{ $plan['discprice'] }}€</span> chaque mois pour 11 mois supplémentaires. Total : <strong class="yc-total">{{ $plan['total_disc_price'] }}€</strong>.</p>
                                            <p class="small">La résiliation n’est disponible que pendant le dernier mois de l’année d’abonnement.</p>
                                        </div>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <!-- NEW Plan Details -->


                <div class="footer-button">
                    <a href="#" class="btn-link" id="current-plan-btn">
                          <span id="plan-name"> </span>
                    </a>
                </div>
            </div>
    </section>

    @section('scripts')
        <script>
            $('.accordion-header').click(function(e) {
                e.preventDefault();
                let $this = $(this);
                if ($this.next().hasClass('show')) {
                    $this.next().removeClass('show');
                    $this.next().slideUp(350);
                }
                else {
                    $this.parent().parent().find('li .accordion-body').removeClass('show');
                    $this.parent().parent().find('li .accordion-body').slideUp(350);
                    $this.next().toggleClass('show');
                    $this.next().slideToggle(350);
                }
            });

            $(document).ready(function() {
                var selectedPlanId = {{ $subscriptionData['plan_id'] }};
                var allPlans = @json($allPlans);
                updateButtonText(selectedPlanId);

                $('.accordion-header').click(function(e) {
                    e.preventDefault();
                    let $this = $(this);
                    var planId = $this.closest('li').data('plan-id');
                    updateButtonText(planId);
                });

                function updateButtonText(planId) {
                    var selectedPlan = allPlans.find(function(plan) {
                        return plan.id == planId;
                    });
                    if (selectedPlan) {

                        const currentPlanId = {{ $subscriptionData['plan_id'] }};
                        const nextPlanId = {!! json_encode($subscriptionData['next_plan_id']) !!};
                        const nextPlanEmpty = (nextPlanId === null || nextPlanId === "");

                        // 1️⃣ Selected plan IS current plan AND next_plan_id is null/empty
                        if (selectedPlan.id == currentPlanId && nextPlanEmpty) {
                            $('#new-start-date').hide();
                            $('#current-plan-btn').attr('href', 'javascript:window.history.back();');
                            $('#current-plan-btn').html('Garder mon forfait de <span id="plan-name">' + selectedPlan.name + '</span>');

                        // 2️⃣ Selected plan IS current plan AND next_plan_id is NOT null/empty
                        } else if (selectedPlan.id == currentPlanId && !nextPlanEmpty) {

                            $('#new-start-date').show();
                            $('#current-plan-btn').attr('href', '/change-plan/' + selectedPlan.id);
                            $('#current-plan-btn').html('Changer d’avis, rester sur mon forfait actuel');

                        // 3️⃣ Selected plan IS the upcoming plan
                        } else if (selectedPlan.id == nextPlanId) {

                            $('#new-start-date').hide();
                            $('#current-plan-btn').attr('href', 'javascript:window.history.back();');
                            $('#current-plan-btn').html('Ceci est votre futur forfait');

                        // 4️⃣ Any other plan
                        } else {
                            $('#new-start-date').show();
                            $('#current-plan-btn').attr('href', '/change-plan/' + selectedPlan.id);
                            $('#current-plan-btn').html('Changer pour le plan <span id="plan-name">' + selectedPlan.name + '</span>');
                        }
                    }
                }
            });
        </script>
    @endsection
</x-home-layout>
