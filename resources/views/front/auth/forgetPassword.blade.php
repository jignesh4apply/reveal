<x-home-layout title="Forgot Password">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <style>
        body {
            margin: 0;
            color: #00120B;
            font-size: 14px;
            line-height: 20px;
            font-family: 'SF Pro Text';
        }

        * {
            box-sizing: border-box;
        }

        img {
            max-width: 100%;
        }

        p {
            margin: 0;
        }

        a {
            text-decoration: none;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0;
            font-family: 'SFRounded';
        }

        button.btn {
            border: none;
            outline: none;
            box-shadow: none;
        }

        .container {
            max-width: 375px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .space-ptb {
            padding: 24px 0;
        }

        .space-pt {
            padding-top: 24px;
        }

        .space-pb {
            padding-bottom: 24px;
        }

        .section-title {
            margin-bottom: 16px;
        }

        .section-title h2.title {
            font-size: 24px;
            line-height: 29px;
            letter-spacing: -0.48px;
        }

        .section-title h4.title {
            font-size: 20px;
            line-height: 24px;
        }

        .inner-header.age-header {
            margin-bottom: 25px;
            padding-top: 20px;
        }

        .inner-header.age-header .header-top {
            display: flex;
            position: relative;
            text-align: center;
            justify-content: center;
            align-items: center;
        }

        .inner-header.age-header .header-top .logo {
            line-height: 0;
        }

        .inner-header.age-header .header-top .logo .logo-img {
            height: 32px;
            max-height: 32px;
        }



        /*New CSS*/

        .main-section.full-height {
            min-height: 100vh;
        }

        .question-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .question-title .title {
            color: #17191C;
            font-size: 24px;
            line-height: 28px;
            margin: 0;
        }

        .question-title .description {
            margin: 0;
            margin-top: 12px;
        }

        .answers {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .form-input {
            width: 100%;
            height: 62px;
            padding: 0 14px 0 46px;
            border-radius: 12px;
            background-color: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0px 12px 24px 0px rgba(0, 0, 0, 0.1);
            margin: 0;
        }

        .form-input:focus {
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0px 12px 24px 0px rgba(0, 0, 0, 0.1);
            outline: none;
        }

        .input-w-icon {
            position: relative;
        }

        .input-w-icon.second-icon-input .form-input {
            padding-right: 46px;
        }

        .answers .answer {
            position: relative;
            line-height: 0;
        }

        .answer .input-icon {
            position: absolute;
            top: 31px;
            left: 14px;
            transform: translateY(-50%);
        }

        .answer .second-icon {
            position: absolute;
            right: 14px;
            top: 31px;
            transform: translateY(-50%);
            cursor: pointer;
        }

        .answer .hide-pw {
            display: none;
        }

        .answers.single .answer .input-icon {
            font-weight: 500;
        }

        .main-btn,
        .submit-btn {
            font-family: 'SFRounded';
            font-size: 16px;
            line-height: 20px !important;
            letter-spacing: -0.32px;
            height: auto !important;
            font-weight: 600;
            text-align: center;
            width: 100%;
            max-width: 375px;
            padding: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #7662F1 !important;
            border-radius: 50px !important;
            box-shadow: none !important;
            border: none !important;
            color: #ffffff !important;
            margin-bottom: 0 !important;
            margin-top: 30px;
        }

        .main-btn:focus {
            border: 0;
            outline: 0;
            box-shadow: 0px 12px 24px 0px rgba(255, 255, 255, 0.5);
        }



        /* Change Plan  */

        .change-plan .section-title {
            text-align: center;
            margin-bottom: 50px;
        }

        .change-plan .transition-img {
            text-align: center;
        }

        .change-plan .transition-img img {
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
            margin-bottom: 2px;
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
            gap: 4px;
        }

        .change-plan-box .accordion-header-right .plan-main-amount {
            color: rgba(0, 18, 11, 0.6);
            font-size: 28px;
            line-height: 33px;
        }

        .change-plan-box .accordion-header-right .plan-before span {
            color: rgba(0, 18, 11, 0.6);
            font-size: 12px;
            font-weight: 500;
            line-height: 14px;
        }

        .change-plan-box .accordion-header-right .plan-before .plan-before-amount {
            display: block;
            margin-bottom: 1px;
            font-weight: normal;
        }

        .change-plan-box .accordion-body {
            display: none;
            margin-top: 12px;
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

        .title {
            font-size: 24px;
            line-height: 28px;
            letter-spacing: -0.48px;
        }

        .footer-button {
            position: sticky;
            bottom: 0;
            margin: auto;
            border: none;
            font-weight: 600;
            padding: 30px 16px;
            background-color: white;
            margin: 0 -16px;
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


        /* Sign in */

        .sign-in-header {
            padding: 16px 0;
            text-align: center;
        }

        .sign-in-header img {
            height: 32px;
        }

        .main-block {
            padding: 32px 16px 50px 16px;
            background-color: #ffffff;
            border-radius: 32px 32px 0 0;
            min-height: calc(100vh - 100px);
        }

        .question-title.mb-60 {
            margin-bottom: 60px;
        }

        .sign-in-form .answers {
            gap: 48px;
        }

        .sign-in-form .answer label {
            font-family: SFRounded;
            font-size: 14px;
            font-weight: 600;
            line-height: 20px;
            position: absolute;
            top: -32px;
            left: 0;
            color: rgba(0, 18, 11, 0.5);
        }

        .sign-in-form .answer .forgot-pw-btn {
            font-family: SFRounded;
            font-size: 14px;
            font-weight: 600;
            line-height: 20px;
            position: absolute;
            top: -32px;
            right: 0;
            color: #2A3539;
        }

        .terms-condition {
            font-family: SFRounded;
            font-size: 12px;
            font-weight: 700;
            line-height: 16px;
            text-align: center;
            margin-top: 100px;
        }
    </style>
    @endsection

    <body style="margin: 0;font-family: 'SF Pro Text';">

        <section class="main-section">
            <div class="container">
                <div class="inner-section">
                    <div class="main-header">
                        <div class="inner-header age-header">
                            <div class="header-top">
                                <div class="logo">
                                    <img class="logo-img" src="{{ asset('webAssets/images/logo.png') }}" alt="logo">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="question-title">
                        <h3 class="title">Mot de passe oublié</h3>
                    </div>
                    <form action="{{ route('user-forget-password') }}" class="reset-pw-form" method="post">
                        {{ csrf_field() }}
                        <ul class="answers">
                            <li class="answer input-w-icon second-icon-input">
                                <input class="form-input" id="email" type="email" name="email" placeholder="Entrez votre email">
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="input-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.02074 7.34742C2.12622 5.48088 3.67304 4 5.566 4H18.449C20.4105 4 22 5.58997 22 7.552V17C22 19.2093 20.2093 21 18 21H6C3.79072 21 2 19.2093 2 17V7.551C2 7.48124 2.00714 7.41315 2.02074 7.34742ZM4 10.7796V17C4 18.1047 4.89528 19 6 19H18C19.1047 19 20 18.1047 20 17V10.7898L15.9352 13.5457C13.5634 15.1533 10.4517 15.1534 8.07993 13.5458L4 10.7796ZM20 7.552C20 6.69403 19.3055 6 18.449 6H5.566C4.70928 6 4.015 6.69428 4.015 7.551C4.015 8.06556 4.26974 8.54635 4.69595 8.83515L9.20207 11.8902C10.8963 13.0386 13.1187 13.0386 14.8129 11.8902L19.3199 8.8346C19.7452 8.54702 20 8.06673 20 7.552Z" fill="#17191C" />
                                    </svg>
                                </div>
                            </li>
                        </ul>
                        <button type="submit" class="main-btn">Continuer</button>
                    </form>
                </div>
            </div>
        </section>

    </body>
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

    <script src="{{ asset('webAssets/gymlifemaster/js/jquery-3.3.1.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/masonry.pkgd.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery.barfiller.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/jquery.slicknav.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('webAssets/gymlifemaster/js/main.js') }}"></script>
    @endsection
</x-home-layout>
