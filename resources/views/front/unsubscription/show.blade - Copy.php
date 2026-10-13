<x-home-layout title="Cancel Subscription">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        .btn-link {
            background: #FFEC68;
            display: inline-flex;
            padding: 20px 30px;
            color: #000000;
            font-weight: 600;
            font-size: 16px;
            border: 2px solid #0F1214;
            box-shadow: 2px 4px #000;
            border-radius: 8px;
        }

        .second-btn {
            color: #000000;
            margin-top: 50px;
        }

        .feedback-box {
            margin-top: -50px;
            position: relative;
            text-align: center;
        }

        .feedback-input {
            height: 280px;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 14px;
            padding: 20px;
            font-size: 15px;
            line-height: 21px;
            font-family: var(--medium-heading-font);
            font-weight: normal;
            border: 1px solid #e5e5e5;
            box-shadow: 0 4px 14px 0 rgba(0, 0, 0, 0.1);
            resize: vertical;
        }

        .feedback-input::placeholder {
            color: var(--border-color);
            font-size: 15px;
            line-height: 21px;
            font-family: var(--bold-heading-font);
        }

        .feedback-input:focus-visible,
        .feedback-input:focus {
            border: 1px solid #e5e5e5;
            outline: none;
        }
    </style>
    @endsection
    {{-- @dd($screen); --}}
    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <div class="main-header">
                    <div class="inner-header age-header">
                        <a href="#" onclick="goBackOneStep()" class="back-btn">
                            <img src="{{ asset('webAssets/images/svgs/back.svg') }}" alt="Back">
                        </a>
                        <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}" alt="logo">
                        @if($screen->show_title == 1)
                        <h1>{{ $screen->screen_title }}</h1>
                        @endif
                        <div>
                            {{-- @if ($screen->is_screen_image)
                                <img src="{{ asset('storage/'. $screen->screen_image) }}" alt="{{ $screen->screen_title }}">
                            @endif --}}
                            <p>{!! $screen->screen_description !!}</p>

                            @if ($screen->screen_type == 0)
                            <form action="{{ route('feedback.submit', $screen->screen_position) }}" method="POST">
                                @csrf
                                {{-- <div class="details-options">
                                        @foreach ($screen->feedback as $feedback)
                                            @if($screen->feedback_type == "single")
                                                <div class="details-select">
                                                    <input type="radio" id="answer_{{ $feedback['id'] }}" name="feedback_id" class="age-select" value="{{ $feedback['id'] }}" required>
                                <label class="center-label" for="answer_{{ $feedback['id'] }}">{{ $feedback['feedback_options'] }}</label>
                        </div>
                        @elseif($screen->feedback_type == "multiple")
                        <input type="checkbox" id="answer_{{ $feedback['id'] }}" name="feedback_id[]" class="goal-select" value="{{ $feedback['id'] }}">
                        <label class="center-label" for="answer_{{ $feedback['id'] }}">{{ $feedback['feedback_options'] }}</label>
                        @elseif($screen->feedback_type == "userInput")
                        <input type="hidden" id="answer_{{ $feedback['id'] }}" name="feedback_id" class="goal-select" value="{{ $feedback['id'] }}">
                        <div class="feedback-box">
                            <textarea class="feedback-input" id="answer_{{ $feedback['id'] }}" name="feedback" placeholder="{{ $feedback['feedback_options'] }}" required></textarea>
                        </div>
                        @endif
                        @endforeach
                    </div> --}}

                    @if($screen->feedback_type == "single")
                    <div class="details-options">
                        @foreach ($screen->feedback as $feedback)
                        <div class="details-select">
                            <input type="radio" id="answer_{{ $feedback['id'] }}" name="feedback_id" class="age-select" value="{{ $feedback['id'] }}" required>
                            <label class="center-label" for="answer_{{ $feedback['id'] }}">{{ $feedback['feedback_options'] }}</label>
                        </div>
                        @endforeach
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
                        <img src="{{ asset('webAssets\images/seeyougo.png') }}" alt="">
                    </div>
                    @foreach ($screen->feedback as $feedback)
                    <input type="hidden" id="answer_{{ $feedback['id'] }}" name="feedback_id" class="goal-select" value="{{ $feedback['id'] }}">
                    <div class="feedback-box">
                        <textarea class="feedback-input" id="answer_{{ $feedback['id'] }}" name="feedback" placeholder="{{ $feedback['feedback_options'] }}" required></textarea>
                    </div>
                    @endforeach
                    @endif




                    @if($screen->show_default_button == "1")
                    <button class="btn-link" type="submit">{{$screen->button_text ? $screen->button_text: 'Save' }}</button>
                    @endif
                    </form>
                    @else
                    <form action="{{ route('feedback.submit', $screen->screen_position) }}" method="POST">
                        @csrf
                        @if($screen->show_default_button == "1")
                        <button class="btn-link" type="submit">{{$screen->button_text ? $screen->button_text: 'next' }}</button>
                        @endif
                    </form>
                    @endif
                    @if($screen->have_offer == 1)
                    <div>
                        We are giving {{$screen->offer_in_per}} % !!
                    </div>
                    @elseif($screen->have_offer == 2)
                    <div>
                        @php
                        $originalAmount = $userSubscriptionDetail->plan_value;
                        $discountPercentage = $screen->offer_in_per;

                        $discountAmount = ($originalAmount * $discountPercentage) / 100;

                        $newAmount = $originalAmount - $discountAmount;
                        @endphp
                        Current Plan Price : {{ $originalAmount}} &nbsp; </br>
                        Invoice Price after applying {{ $discountPercentage }}% off: {{ number_format($newAmount, 2) }}
                    </div>
                    @endif
                    @if ($screen->is_multiple_button == 1)
                    @foreach ($screen->multiple_buttons_value as $button)
                    @if($button['btn_type'] == "link")
                    <a href="#" class="second-btn second-btn-text" data-redirect-to="{{ $button['redirect_to'] }}" data-manual-link="{{ $button['manual_link'] }}" data-redirect-screen-id="{{ $button['redirect_screen_id'] }}">
                        {{$button['btn_text']}}
                    </a>
                    @elseif($button['btn_type'] == "btn")
                    <a href="#" class="btn-link second-btn-text" data-redirect-to="{{ $button['redirect_to'] }}" data-manual-link="{{ $button['manual_link'] }}" data-redirect-screen-id="{{ $button['redirect_screen_id'] }}">
                        {{$button['btn_text']}}
                    </a>
                    @endif
                    </br>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
        </div>
        </div>
    </section>

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
            $('.second-btn-text').on('click', function(e) {
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
