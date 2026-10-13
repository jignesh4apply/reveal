<x-home-layout title="Reset Password">
    @section('styles')
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            max-height: 30px;
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
            font-weight: 700;
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
                        <h3 class="title">Réinitialiser le mot de passe</h3>
                    </div>

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form id="quiz-form" class="reset-pw-form" action="{{ url('user-reset-password/'. $token) }}" method="post">
                        {{ csrf_field() }}
                        <ul class="answers">
                            <li class="answer input-w-icon second-icon-input">
                                <input class="form-input" id="email" type="email" name="email" placeholder="Entrez votre email" value="{{ old('email') }}">
                                <div class="input-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.02074 7.34742C2.12622 5.48088 3.67304 4 5.566 4H18.449C20.4105 4 22 5.58997 22 7.552V17C22 19.2093 20.2093 21 18 21H6C3.79072 21 2 19.2093 2 17V7.551C2 7.48124 2.00714 7.41315 2.02074 7.34742ZM4 10.7796V17C4 18.1047 4.89528 19 6 19H18C19.1047 19 20 18.1047 20 17V10.7898L15.9352 13.5457C13.5634 15.1533 10.4517 15.1534 8.07993 13.5458L4 10.7796ZM20 7.552C20 6.69403 19.3055 6 18.449 6H5.566C4.70928 6 4.015 6.69428 4.015 7.551C4.015 8.06556 4.26974 8.54635 4.69595 8.83515L9.20207 11.8902C10.8963 13.0386 13.1187 13.0386 14.8129 11.8902L19.3199 8.8346C19.7452 8.54702 20 8.06673 20 7.552Z" fill="#17191C" />
                                    </svg>
                                </div>
                            </li>
                            <li class="answer input-w-icon second-icon-input">
                                <input class="form-input" id="password" type="password" name="password" placeholder="Entrez un nouveau mot de passe">
                                <div class="input-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.13892 13.6854C1.01739 6.85154 6.85176 1.0162 13.6867 2.13798C17.8403 2.81948 21.1801 6.16054 21.8616 10.3139C22.9822 17.1488 17.1478 22.9823 10.3139 21.8616C6.16072 21.1802 2.81947 17.8401 2.13892 13.6854ZM13.3628 4.11157C7.87791 3.21137 3.21237 7.8778 4.11256 13.3618C4.65415 16.6689 7.33098 19.3454 10.6376 19.888C16.1217 20.7873 20.7873 16.1226 19.8879 10.6376C19.3454 7.33095 16.6691 4.65406 13.3628 4.11157ZM9.70686 11.2927C9.31634 10.9022 8.68317 10.9022 8.29265 11.2927C7.90212 11.6832 7.90212 12.3164 8.29265 12.7069C8.68317 13.0974 9.31634 13.0974 9.70686 12.7069C10.0974 12.3164 10.0974 11.6832 9.70686 11.2927ZM6.87843 9.87847C8.05001 8.7069 9.9495 8.7069 11.1211 9.87847C11.4486 10.206 11.6845 10.5903 11.8289 10.9998H16.9998C17.5521 10.9998 17.9998 11.4475 17.9998 11.9998V13.8598C17.9998 14.4121 17.5521 14.8598 16.9998 14.8598C16.4475 14.8598 15.9998 14.4121 15.9998 13.8598V12.9998H14.9998V13.8598C14.9998 14.4121 14.5521 14.8598 13.9998 14.8598C13.4475 14.8598 12.9998 14.4121 12.9998 13.8598V12.9998H11.8289C11.6845 13.4092 11.4486 13.7936 11.1211 14.1211C9.9495 15.2927 8.05001 15.2927 6.87843 14.1211C5.70686 12.9495 5.70686 11.05 6.87843 9.87847Z" fill="#17191C" />
                                    </svg>
                                </div>
                                <div class="second-icon" onclick="togglePasswordVisibility('password', this)">
                                    <div class="show-pw" style="display: none">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.122 9.88C15.293 11.051 15.293 12.952 14.122 14.125C12.951 15.296 11.05 15.296 9.877 14.125C8.706 12.954 8.706 11.053 9.877 9.88C11.05 8.707 12.95 8.707 14.122 9.88Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 11.341 3.152 10.689 3.446 10.088C4.961 6.991 8.309 5 12 5C15.691 5 19.039 6.991 20.554 10.088C20.848 10.689 21 11.341 21 12C21 12.659 20.848 13.311 20.554 13.912C19.039 17.009 15.691 19 12 19C8.309 19 4.961 17.009 3.446 13.912C3.152 13.311 3 12.659 3 12Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="hide-pw" style="display: block">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0" />
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" />
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.60603 6.08062C2.11366 5.86307 2.70154 6.09822 2.9191 6.60585L1.99995 6.99977C2.9191 6.60585 2.91924 6.60618 2.9191 6.60585L2.91858 6.60465C2.9183 6.604 2.91851 6.60447 2.91858 6.60465L2.9225 6.61351C2.92651 6.62253 2.93339 6.63785 2.94319 6.65905C2.96278 6.70147 2.99397 6.76735 3.03696 6.85334C3.12302 7.02546 3.25594 7.27722 3.43737 7.58203C3.80137 8.19355 4.35439 9.00801 5.10775 9.81932C5.28532 10.0105 5.47324 10.2009 5.67173 10.3878C5.68003 10.3954 5.68823 10.4031 5.69633 10.4109C7.18102 11.8012 9.25227 12.9998 12 12.9998C13.2089 12.9998 14.2783 12.769 15.2209 12.398C16.4469 11.9154 17.4745 11.1889 18.3156 10.3995C19.2652 9.50815 19.9627 8.54981 20.4232 7.81076C20.6526 7.44268 20.8207 7.13295 20.9299 6.91886C20.9844 6.81192 21.0241 6.72919 21.0491 6.67538C21.0617 6.64848 21.0706 6.62884 21.0758 6.61704L21.0808 6.60585C21.2985 6.0985 21.8864 5.86312 22.3939 6.08062C22.9015 6.29818 23.1367 6.88606 22.9191 7.39369L22 6.99977C22.9191 7.39369 22.9192 7.39346 22.9191 7.39369L22.9169 7.39871L22.9134 7.40693L22.9019 7.43278C22.8924 7.4541 22.879 7.48354 22.8618 7.52048C22.8274 7.59434 22.7774 7.69831 22.7115 7.8275C22.5799 8.08566 22.384 8.44584 22.1206 8.86844C21.718 9.5146 21.152 10.316 20.4096 11.1241L21.2071 11.9215C21.5976 12.312 21.5976 12.9452 21.2071 13.3357C20.8165 13.7262 20.1834 13.7262 19.7928 13.3357L18.9527 12.4955C18.3884 12.9513 17.757 13.3811 17.0558 13.752L17.8381 14.9544C18.1393 15.4173 18.0083 16.0367 17.5453 16.338C17.0824 16.6392 16.463 16.5081 16.1618 16.0452L15.1763 14.5306C14.4973 14.7388 13.772 14.8863 13 14.9554V16.4998C13 17.0521 12.5522 17.4998 12 17.4998C11.4477 17.4998 11 17.0521 11 16.4998V14.9556C10.2253 14.8864 9.50014 14.7386 8.82334 14.531L7.83814 16.0452C7.53693 16.5081 6.91748 16.6392 6.45457 16.338C5.99165 16.0367 5.86056 15.4173 6.16177 14.9544L6.94417 13.7519C6.24405 13.3814 5.61245 12.9515 5.04746 12.4953L4.20706 13.3357C3.81654 13.7262 3.18337 13.7262 2.79285 13.3357C2.40232 12.9452 2.40232 12.312 2.79285 11.9215L3.59029 11.1241C2.74529 10.2043 2.12772 9.292 1.71879 8.605C1.5096 8.25356 1.35345 7.95845 1.2481 7.74776C1.19539 7.64234 1.15529 7.55783 1.12752 7.49771C1.11363 7.46765 1.10282 7.44366 1.09505 7.42618L1.08566 7.4049L1.08267 7.39801L1.0816 7.39553L1.08117 7.39453C1.08098 7.39409 1.08081 7.39369 1.99995 6.99977L1.08117 7.39453C0.863613 6.8869 1.0984 6.29818 1.60603 6.08062Z" fill="#17191C99" />
                                            </g>
                                        </svg>
                                    </div>
                                </div>
                            </li>
                            <li class="answer input-w-icon second-icon-input">
                                <input class="form-input" id="cpassword" type="password" name="cpassword" placeholder="Confirmez le mot de passe">
                                <div class="input-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M2.13892 13.6854C1.01739 6.85154 6.85176 1.0162 13.6867 2.13798C17.8403 2.81948 21.1801 6.16054 21.8616 10.3139C22.9822 17.1488 17.1478 22.9823 10.3139 21.8616C6.16072 21.1802 2.81947 17.8401 2.13892 13.6854ZM13.3628 4.11157C7.87791 3.21137 3.21237 7.8778 4.11256 13.3618C4.65415 16.6689 7.33098 19.3454 10.6376 19.888C16.1217 20.7873 20.7873 16.1226 19.8879 10.6376C19.3454 7.33095 16.6691 4.65406 13.3628 4.11157ZM9.70686 11.2927C9.31634 10.9022 8.68317 10.9022 8.29265 11.2927C7.90212 11.6832 7.90212 12.3164 8.29265 12.7069C8.68317 13.0974 9.31634 13.0974 9.70686 12.7069C10.0974 12.3164 10.0974 11.6832 9.70686 11.2927ZM6.87843 9.87847C8.05001 8.7069 9.9495 8.7069 11.1211 9.87847C11.4486 10.206 11.6845 10.5903 11.8289 10.9998H16.9998C17.5521 10.9998 17.9998 11.4475 17.9998 11.9998V13.8598C17.9998 14.4121 17.5521 14.8598 16.9998 14.8598C16.4475 14.8598 15.9998 14.4121 15.9998 13.8598V12.9998H14.9998V13.8598C14.9998 14.4121 14.5521 14.8598 13.9998 14.8598C13.4475 14.8598 12.9998 14.4121 12.9998 13.8598V12.9998H11.8289C11.6845 13.4092 11.4486 13.7936 11.1211 14.1211C9.9495 15.2927 8.05001 15.2927 6.87843 14.1211C5.70686 12.9495 5.70686 11.05 6.87843 9.87847Z" fill="#17191C" />
                                    </svg>
                                </div>
                                <div class="second-icon" onclick="togglePasswordVisibility('cpassword', this)">
                                    <div class="show-pw" style="display: none">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M14.122 9.88C15.293 11.051 15.293 12.952 14.122 14.125C12.951 15.296 11.05 15.296 9.877 14.125C8.706 12.954 8.706 11.053 9.877 9.88C11.05 8.707 12.95 8.707 14.122 9.88Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M3 12C3 11.341 3.152 10.689 3.446 10.088C4.961 6.991 8.309 5 12 5C15.691 5 19.039 6.991 20.554 10.088C20.848 10.689 21 11.341 21 12C21 12.659 20.848 13.311 20.554 13.912C19.039 17.009 15.691 19 12 19C8.309 19 4.961 17.009 3.446 13.912C3.152 13.311 3 12.659 3 12Z" stroke="#17191C" stroke-opacity="0.6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="hide-pw" style="display: block">
                                        <svg width="24px" height="24px" viewBox="0 0 24 24" fill="none">
                                            <g id="SVGRepo_bgCarrier" stroke-width="0" />
                                            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" />
                                            <g id="SVGRepo_iconCarrier">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M1.60603 6.08062C2.11366 5.86307 2.70154 6.09822 2.9191 6.60585L1.99995 6.99977C2.9191 6.60585 2.91924 6.60618 2.9191 6.60585L2.91858 6.60465C2.9183 6.604 2.91851 6.60447 2.91858 6.60465L2.9225 6.61351C2.92651 6.62253 2.93339 6.63785 2.94319 6.65905C2.96278 6.70147 2.99397 6.76735 3.03696 6.85334C3.12302 7.02546 3.25594 7.27722 3.43737 7.58203C3.80137 8.19355 4.35439 9.00801 5.10775 9.81932C5.28532 10.0105 5.47324 10.2009 5.67173 10.3878C5.68003 10.3954 5.68823 10.4031 5.69633 10.4109C7.18102 11.8012 9.25227 12.9998 12 12.9998C13.2089 12.9998 14.2783 12.769 15.2209 12.398C16.4469 11.9154 17.4745 11.1889 18.3156 10.3995C19.2652 9.50815 19.9627 8.54981 20.4232 7.81076C20.6526 7.44268 20.8207 7.13295 20.9299 6.91886C20.9844 6.81192 21.0241 6.72919 21.0491 6.67538C21.0617 6.64848 21.0706 6.62884 21.0758 6.61704L21.0808 6.60585C21.2985 6.0985 21.8864 5.86312 22.3939 6.08062C22.9015 6.29818 23.1367 6.88606 22.9191 7.39369L22 6.99977C22.9191 7.39369 22.9192 7.39346 22.9191 7.39369L22.9169 7.39871L22.9134 7.40693L22.9019 7.43278C22.8924 7.4541 22.879 7.48354 22.8618 7.52048C22.8274 7.59434 22.7774 7.69831 22.7115 7.8275C22.5799 8.08566 22.384 8.44584 22.1206 8.86844C21.718 9.5146 21.152 10.316 20.4096 11.1241L21.2071 11.9215C21.5976 12.312 21.5976 12.9452 21.2071 13.3357C20.8165 13.7262 20.1834 13.7262 19.7928 13.3357L18.9527 12.4955C18.3884 12.9513 17.757 13.3811 17.0558 13.752L17.8381 14.9544C18.1393 15.4173 18.0083 16.0367 17.5453 16.338C17.0824 16.6392 16.463 16.5081 16.1618 16.0452L15.1763 14.5306C14.4973 14.7388 13.772 14.8863 13 14.9554V16.4998C13 17.0521 12.5522 17.4998 12 17.4998C11.4477 17.4998 11 17.0521 11 16.4998V14.9556C10.2253 14.8864 9.50014 14.7386 8.82334 14.531L7.83814 16.0452C7.53693 16.5081 6.91748 16.6392 6.45457 16.338C5.99165 16.0367 5.86056 15.4173 6.16177 14.9544L6.94417 13.7519C6.24405 13.3814 5.61245 12.9515 5.04746 12.4953L4.20706 13.3357C3.81654 13.7262 3.18337 13.7262 2.79285 13.3357C2.40232 12.9452 2.40232 12.312 2.79285 11.9215L3.59029 11.1241C2.74529 10.2043 2.12772 9.292 1.71879 8.605C1.5096 8.25356 1.35345 7.95845 1.2481 7.74776C1.19539 7.64234 1.15529 7.55783 1.12752 7.49771C1.11363 7.46765 1.10282 7.44366 1.09505 7.42618L1.08566 7.4049L1.08267 7.39801L1.0816 7.39553L1.08117 7.39453C1.08098 7.39409 1.08081 7.39369 1.99995 6.99977L1.08117 7.39453C0.863613 6.8869 1.0984 6.29818 1.60603 6.08062Z" fill="#17191C99" />
                                            </g>
                                        </svg>
                                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script>
        $('#login-form').validate({
            rules: {
                email: {
                    required: true,
                },
                password: {
                    required: true
                }
            },
            messages: {
                email: {
                    required: "Please enter your email",
                },
                Password: {
                    required: "Please enter Password",
                }
            },
            errorElement: 'div',
            errorPlacement: function(error, element) {
                error.addClass('invalid-feedback');
                element.closest('.form-group').append(error);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalid');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalid');
            }
        });
    </script>
    <script>
        function togglePasswordVisibility(passwordFieldId, iconElement) {
            const passwordField = document.getElementById(passwordFieldId);
            const showIcon = iconElement.querySelector('.show-pw');
            const hideIcon = iconElement.querySelector('.hide-pw');

            // Toggle the type of password field
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                showIcon.style.display = 'block';
                hideIcon.style.display = 'none';
            } else {
                passwordField.type = 'password';
                showIcon.style.display = 'none';
                hideIcon.style.display = 'block';
            }
        }
    </script>
    @endsection
</x-home-layout>