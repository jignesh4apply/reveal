<x-home-layout title="Questions">
    @section('styles')
    <style type="text/css">
        @keyframes spin {
            0% {
                transform: rotate(0);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        body {
            background: url(afterPaywall/images/bg.svg) no-repeat;
            font-family: 'SF Pro Text';
            background-size: cover;
        }

        .main-section {
            min-height: 100vh;
        }

        .container {
            max-width: 375px;
            margin: 0 auto;
        }

        .inner-section {
            display: flex;
            flex-direction: column;
        }

        .main-header {
            text-align: center;
            margin: 25px 0;
        }

        .img-fluid.logo {
            height: 32px;
            max-width: 100%;
        }

        .content-wrapper {
            text-align: center;
        }

        .start-box .box-bg {
            margin-bottom: 20px;
            max-width: 100%;
            height: auto;
        }

        .title {
            font-size: 28px;
            line-height: 34px;
            font-weight: 700;
            color: #00120B;
            margin: 0 0 12px;
        }

        .description {
            font-size: 16px;
            line-height: 22px;
            font-weight: 400;
            color: #00120B;
            margin: 0 0 12px;
        }

        .time-list {
            background-color: #ffffff;
            border-radius: 50px;
            padding: 4px 8px 4px 4px;
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .time-list i {
            font-size: 16px;
            color: #18B45C;
            padding-right: 4px;
        }

        .time-list span {
            font-weight: 500;
            color: #18B45C;
            line-height: 14px;
            font-size: 12px;
        }

        .loader-box {
            display: none;
            margin: 30px 0 25px;
            text-align: center
        }

        .loader {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }

        .loader div {
            line-height: 0;
        }

        .loader div svg {
            animation: spin 2s linear infinite;
        }

        .loader>span {
            font-family: 'SFRounded';
            font-size: 16px;
            line-height: 20px;
            letter-spacing: -0.32px;
            font-weight: 600;
        }

        .skill-wrapper {
            padding: 0;
            width: 100%;
        }

        .progress {
            width: 100%;
            height: 24px;
            background: #f2f3f1;
            border: 2px solid #f2f3f1;
            border-radius: 50px;
            overflow: hidden;
        }

        .bar {
            width: 0%;
            height: 100%;
            background: #1BC666;
            border-radius: 50px;
            position: relative;
        }

        .percent {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #ffffff;
        }

        .fixed-button {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            margin: auto;
            padding-bottom: 20px;
        }

        button {
            padding: 10px 20px;
            background-color: #1BC666;
            color: #fff;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
    @endsection
    <section class="main-section">
        <div class="container">
            <div class="inner-section">
                <header class="main-header">
                    <img class="img-fluid logo" src="{{ asset('paywall/images/logo.svg') }}">
                </header>
                <div class="content-wrapper">
                    <div class="start-box">
                        <img class="box-bg img-fluid" src="{{ asset('afterPaywall/images/bg-01.png') }}">
                        <div class="time-list">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Seulement 2 minutes</span>
                        </div>
                        <h2 class="title">Perds de la graisse, pas seulement du poids !</h2>
                        <p class="description">Réponds à un questionnaire rapide pour obtenir ton plan personnalisé et transformer ta vie.</p>
                    </div>
                    <div class="loader-box">
                        <div class="loader">
                            <div>
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.99918 14.7619C12.1813 14.7619 14.761 12.1823 14.761 9.00015C14.761 5.81802 12.1813 3.23838 8.99918 3.23838C5.81704 3.23838 3.2374 5.81802 3.2374 9.00015C3.2374 12.1823 5.81704 14.7619 8.99918 14.7619ZM8.99918 17.4212C13.65 17.4212 17.4202 13.651 17.4202 9.00015C17.4202 4.34934 13.65 0.579102 8.99918 0.579102C4.34836 0.579102 0.578125 4.34934 0.578125 9.00015C0.578125 13.651 4.34836 17.4212 8.99918 17.4212Z" fill="#00120B" fill-opacity="0.05"></path>
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M8.99918 3.10542C5.7436 3.10542 3.10444 5.74458 3.10444 9.00015C3.10444 12.2557 5.7436 14.8949 8.99918 14.8949C9.6968 14.8949 10.2623 15.4604 10.2623 16.158C10.2623 16.8557 9.6968 17.4212 8.99918 17.4212C4.34836 17.4212 0.578125 13.651 0.578125 9.00015C0.578125 4.34934 4.34836 0.579102 8.99918 0.579102C13.65 0.579102 17.4202 4.34934 17.4202 9.00015C17.4202 9.69778 16.8547 10.2633 16.1571 10.2633C15.4595 10.2633 14.8939 9.69778 14.8939 9.00015C14.8939 5.74458 12.2548 3.10542 8.99918 3.10542Z" fill="#1BC666"></path>
                                </svg>
                            </div>
                            <span>Chargement du quiz...</span>
                        </div>
                        <div class="skill-wrapper">
                            <div class="progress">
                                <div class="bar" id="myBar">
                                    <p class="percent" id="percentText">0%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @section('scripts')
    <script>window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {"fetchRemoteConfig":true,"autocapture":true});</script>
	<script>
        amplitude.track('TLP');
	</script>
    <script>
        $(document).ready(function() {
            let width = 0;

            $('.loader-box').show();
            $('#startButton').hide();
            width = 0;
            $('#myBar').css('width', width + '%');
            $('#percentText').text(width + '%');

            let loadingInterval = setInterval(function() {
                if (width >= 100) { // Load to 100%
                    clearInterval(loadingInterval);
                    $('#startButton').hide();
                    window.location.href = "{{ route('questions') }}";
                } else {
                    width += (100 / (4000 / 20));
                    $('#myBar').css('width', width + '%');
                    $('#percentText').text(Math.round(width) + '%');
                }
            }, 20);
        });
    </script>
    @endsection
</x-home-layout>
