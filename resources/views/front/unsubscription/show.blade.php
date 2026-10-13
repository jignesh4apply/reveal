<x-home-layout title="Cancel Subscription">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <!-- Fonts  -->
    <link rel="stylesheet" href="{{ asset('webAssets/subscription/css/fontawesome/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('webAssets/subscription/font/sf-ui-display/style.css') }}">
    <link rel="stylesheet" href="{{ asset('webAssets/subscription/font/EB_Garamond/stylesheet.css') }}">
    <link rel="stylesheet" href="{{ asset('webAssets/subscription/font/lato/stylesheet.css') }}">
    <!-- Style CSS -->
    <link rel="stylesheet" href="{{ asset('webAssets/subscription/css/style.css') }}">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="{{ asset('webAssets/subscription/css/responsive.css') }}">
    @endsection

    @php
    $discountPercentage = $screen->offer_in_per;
    $subscriptionMonthCycle = $userSubscriptionDetail->billing_cycle;

    $originalAmount = $userSubscriptionDetail->amount;
    $originalPerMonthAmount =  $originalAmount / $subscriptionMonthCycle ;
    $originalPerYearAmount = $originalPerMonthAmount * 12 ;

    $discountAmount = ($originalAmount * $discountPercentage) / 100;

    $newAmount = $originalAmount - $discountAmount;
    $discountedPerMonthAmount =  $newAmount / $subscriptionMonthCycle ;
    $discountedPerYearAmount = $discountedPerMonthAmount * 12 ;
    @endphp

    <div class="main-section">
        <header class="main-header">
            <div class="container">
                <div class="inner-header age-header">
                    @if (!session('hide_back_btn'))
                    <a href="#" onclick="goBackOneStep()" class="back-btn">
                        <img src="{{ asset('webAssets/images/svgs/back.svg') }}" alt="Back">
                    </a>
                    @endif
                    {{-- <a href="#" class="close-btn">
                        <img src="{{ asset('webAssets/subscription/images/svgs/close.svg') }}" alt="Back">
                    </a> --}}
                    <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}" alt="logo">
                    <div class="header-title">cancel subscription</div>
                </div>
            </div>
        </header>
        <section class="subscription-content">
            <div class="container">
                <div class="subscription-body">
                    <div class="subscription-top">
                        @if($screen->show_title == 1)
                        <h1 class="subscription-title">{{ $screen->screen_title }}</h1>
                        @endif
                        <p class="subscription-description">{!! $screen->screen_description !!}</p>

                        @if ($screen->screen_type == 0)
                        <form action="{{ route('feedback.submit', $screen->screen_position) }}" method="POST">
                            @csrf

                            @if($screen->feedback_type == "single")
                            <div class="question-block">
                                <ul class="question-options">
                                    @foreach ($screen->feedback as $feedback)
                                    <li class="question-select no-padding no-border">
                                        <input type="radio" id="answer_{{ $feedback['id'] }}" name="feedback_id" class="age-select" value="{{ $feedback['id'] }}" required>
                                        <label class="center-label" for="answer_{{ $feedback['id'] }}">{{ $feedback['feedback_options'] }}</label>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @elseif($screen->feedback_type == "multiple")
                            <div class="details-options">
                                @foreach ($screen->feedback as $feedback)
                                <input type="checkbox" id="answer_{{ $feedback['id'] }}" name="feedback_id[]" class="goal-select" value="{{ $feedback['id'] }}">
                                <label class="center-label" for="answer_{{ $feedback['id'] }}">{{ $feedback['feedback_options'] }}</label>
                                @endforeach
                            </div>
                            @elseif($screen->feedback_type == "userInput")
                            <div class="going-soon-img">
                                <img src="{{ asset('webAssets/images/seeyougo.png') }}" alt="">
                            </div>
                            @foreach ($screen->feedback as $feedback)
                            <input type="hidden" id="answer_{{ $feedback['id'] }}" name="feedback_id" class="goal-select" value="{{ $feedback['id'] }}">
                            <div class="feedback-box">
                                <textarea class="feedback-input" id="answer_{{ $feedback['id'] }}" name="feedback" placeholder="{{ $feedback['feedback_options'] }}" required></textarea>
                            </div>
                            @endforeach
                            @endif

                            @if($screen->show_default_button == "1")
                            <div class="subscription-footer text-align-center">
                                <div class="main-btn">
                                    <button class="main-btn-text" type="submit">{{$screen->button_text ? $screen->button_text: 'Save' }}</button>
                                </div>
                            </div>
                            @endif
                        </form>
                        @else
                        <form action="{{ route('feedback.submit', $screen->screen_position) }}" method="POST">
                            @csrf
                            @if($screen->show_default_button == "1")
                            <div class="main-btn">
                                <button class="btn-link" type="submit">{{$screen->button_text ? $screen->button_text: 'next' }}</button>
                            </div>
                            @endif
                        </form>
                        @endif
                        @if($screen->have_offer == 1)
                        <div class="going-soon-img center text-align-center">
                            <img src="{{ asset('webAssets/subscription/images/thankyoufeedback.png') }}" alt="">
                        </div>
                        <div class="price-box-block1">
                            <div class="single-price-box text-align-center">
                                <div class="price-box-sub-title">Formule actuel</div>
                                <h5 class="price-box-main-title">{{ $subscriptionMonthCycle }} months of Coaching</h5>
                                <div class="price-box-img">
                                    <img src="{{ asset('webAssets/subscription/images/3month.png') }}" class="img-3m" alt="">
                                    <div class="time-box">
                                        <img src="{{ asset('webAssets/subscription/images/svgs/checkedblue.svg') }}" alt="">
                                        <span class="plan-time">{{ $subscriptionMonthCycle }}M</span>
                                    </div>
                                </div>
                                <p class="price-detail">old price :</p>
                                <h5 class="price">{{ number_format($originalPerMonthAmount, 2)}} €</h5>
                                <p class="plan-duration">for month</p>
                                <span class="yearly-price">be {{ number_format($originalPerYearAmount, 2)}}€/year</span>
                            </div>
                            <div class="single-price-box text-align-center style-2">
                                <div class="price-box-sub-title">Formule mis à jour</div>
                                <h5 class="price-box-main-title">{{ $subscriptionMonthCycle }} months of Coaching</h5>
                                <div class="price-box-img">
                                    <img src="{{ asset('webAssets/subscription/images/3month.png') }}" class="img-3m" alt="">
                                    <div class="time-box">
                                        <img src="{{ asset('webAssets/subscription/images/svgs/checkedgreen.svg') }}" alt="">
                                        <span class="plan-time">{{ $subscriptionMonthCycle }}M</span>
                                    </div>
                                </div>
                                <p class="price-detail">new price :</p>
                                <h5 class="price">{{ number_format($discountedPerMonthAmount, 2)}} € <span class="discount">-{{$discountPercentage}}%</span></h5>
                                <p class="plan-duration">for month</p>
                                <span class="yearly-price">be {{ number_format($discountedPerYearAmount, 2) }}€/year</span>
                            </div>
                            <div class="direction-block">
                                <img src="{{ asset('webAssets/subscription/images/svgs/arrowwhite.svg') }}" alt="">
                            </div>
                        </div>
                        @elseif($screen->have_offer == 2)
                        <div class="plan-overview-block">
                            <div class="overview-img text-align-center">
                                <img src="{{ asset('webAssets/subscription/images/overview.png') }}" alt="">
                            </div>
                            <div class="overview-title">Overview:</div>
                            <ul class="plan-overview-list">
                                <li class="overview-item">
                                    <p class="overview-detail">{{ $subscriptionMonthCycle }} months of coaching</p>
                                    <div class="overview-price">{{ number_format($newAmount, 2) }} €</div>
                                </li>
                                <li class="overview-item">
                                    <p class="overview-detail">Club bracelet</p>
                                    <div class="overview-price">
                                        <span class="before-price">7,60€</span>
                                        0,00 €
                                    </div>
                                </li>
                                <hr>
                                <li class="overview-item">
                                    <p class="overview-detail total-price-detail">Charged amount at the end of your trial.</p>
                                    <div class="overview-price total-price">
                                        <span class="before-price">{{ number_format($newAmount + 760, 2) }}€</span>
                                        {{ number_format($newAmount, 2) }} €
                                    </div>
                                </li>
                            </ul>
                            <p class="plan-monthly-detail text-align-center margin-top-15">
                                That's
                                <span class="highlighted-text full">
                                    <span class="plan-monthly-amount">{{ number_format($discountedPerMonthAmount, 2) }} €</span>/month
                                </span>
                            </p>
                        </div>
                        @endif
                    </div>
                    @if (!session('hide_btn'))
                    <div class="subscription-footer text-align-center">
                        @if ($screen->is_multiple_button == 1)
                        @foreach ($screen->multiple_buttons_value as $button)
                        @if($button['btn_type'] == "link")
                        <div class="second-btn">
                            <a href="#" class="second-btn-text extra-btn" data-redirect-to="{{ $button['redirect_to'] }}" data-manual-link="{{ $button['manual_link'] }}" data-redirect-screen-id="{{ $button['redirect_screen_id'] }}">
                                {{$button['btn_text']}}
                            </a>
                        </div>
                        @elseif($button['btn_type'] == "btn")
                        <div class="main-btn">
                            <a href="#" class="main-btn-text extra-btn" data-redirect-to="{{ $button['redirect_to'] }}" data-manual-link="{{ $button['manual_link'] }}" data-redirect-screen-id="{{ $button['redirect_screen_id'] }}">
                                {{$button['btn_text']}}
                            </a>
                        </div>
                        @endif
                        </br>
                        @endforeach
                        @endif
                    </div>
                    @else
                    <br>
                    <div class="main-btn">
                        <a href="{{ route('user-login') }}" class="main-btn-text">
                            Return To App
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    @section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        @if(Session::has('success'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.success("{{ session('success') }}");
        @endif

        @if(Session::has('error'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.error("{{ session('error') }}");
        @endif
    </script>
    <script>
        $(document).ready(function() {
            $('.extra-btn').on('click', function(e) {
                e.preventDefault();
                var redirectTo = $(this).data('redirect-to');
                var manualLink = $(this).data('manual-link');
                var redirectScreenId = $(this).data('redirect-screen-id');

                if (redirectTo == 1) {
                    // Ensure the manual link is an absolute URL
                    if (manualLink.startsWith('http://') || manualLink.startsWith('https://')) {
                        window.location.href = manualLink;
                    } else {
                        console.error('Manual link is not an absolute URL:', manualLink);
                    }
                } else if (redirectTo == 2) {
                    $.ajax({
                        url: '/get-screen-position/' + redirectScreenId,
                        type: 'GET',
                        success: function(data) {
                            var screenPosition = data.screen_position;
                            window.location.href = '/feedback/' + screenPosition;
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                        }
                    });
                }
            });
        });
    </script>
    <script>
        var stepsBack = 1; // Start with one step back
        function goBackOneStep() {
            if (history.length > 1) {
                history.go(-stepsBack);
                stepsBack++;
            } else {
                alert("You are already on the first page visited in this session.");
            }
        }
    </script>

    @endsection
</x-home-layout>
