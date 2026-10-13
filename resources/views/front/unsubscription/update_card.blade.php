<x-after-paywall-layout title="Subscription details">
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

        h1,h2,h3,h4,h5,h6 {
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

        .question-title.text-align-left {
            text-align: left;
        }

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
            box-shadow: 0px 12px 24px -8px rgba(0, 0, 0, 0.1);
            margin: 0;
        }

        .form-input:focus {
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0px 12px 24px -8px rgba(0, 0, 0, 0.1);
            outline: none;
        }

        .answers .answer {
            position: relative;
            line-height: 0;
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
            gap: 4px;
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

        /* Update Card */

        .update-card {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 20px 16px;
            box-shadow: 0px 12px 24px -8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .update-card .update-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .update-card-top .card-img-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .answer.input-no-icon .form-input {
            padding: 16px 14px;
        }

        .answer.input-half {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .answer.input-half .form-input {
            width: 100%;
        }

        .update-card .divider {
            height: 1px;
            position: relative;
            background-color: rgba(0, 18, 11, 0.1);
            margin: 24px 0;
        }

        .update-card .divider span {
            display: inline-block;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 0 8px;
            background-color: #ffffff;
            font-size: 14px;
            font-weight: 400;
            line-height: 16px;
            color: rgba(0, 18, 11, 0.3);
        }

        .apple-pay-btn {
            font-family: 'SFRounded';
            font-size: 16px;
            line-height: 20px !important;
            letter-spacing: -0.32px;
            height: auto !important;
            font-weight: 600;
            text-align: center;
            width: 100%;
            max-width: 375px;
            padding: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #00120B !important;
            border-radius: 50px !important;
            box-shadow: none !important;
            border: none !important;
            color: #ffffff !important;
            margin-bottom: 0 !important;
            margin-top: 30px;
        }

        .paypal-btn {
            font-family: 'SFRounded';
            font-size: 16px;
            line-height: 20px !important;
            letter-spacing: -0.32px;
            height: auto !important;
            font-weight: 600;
            text-align: center;
            width: 100%;
            max-width: 375px;
            padding: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #FFC43A !important;
            border-radius: 50px !important;
            box-shadow: none !important;
            border: none !important;
            color: #ffffff !important;
            margin-bottom: 0 !important;
            margin-top: 8px;
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
                          <img class="logo-img" src="{{ asset('subscriptionDetails/images/logo.png') }}" alt="logo">
                        </div>
                      </div>
                    </div>
                  </div>
          
                  <div class="question-title">
                    <h3 class="title">Mettre à jour votre carte</h3>
                  </div>
          
                  <div class="update-card">
                    <div class="update-card-top">
                        <div class="card-img-wrapper">
                            <img class="visa" src="{{ asset('paywall/images/svgs/visa.svg') }}" alt="visa">
                            <img src="{{ asset('paywall/images/svgs/mastercard.svg') }}" alt="mastercard">
                            <img src="{{ asset('paywall/images/svgs/maestro.svg') }}" alt="maestro">
                            <img src="{{ asset('paywall/images/svgs/ae.svg') }}" alt="american express">
                        </div>
                        <img class="stripe" src="{{ asset('paywall/images/svgs/stripe.svg') }}" alt="stripe">
                    </div>
                    <form action="{{ route('update-card', ['custId' => $cust_id]) }}" method="POST" id="payment-form">
                        @csrf
                        <div id="card-element"><!-- A Stripe Element will be inserted here. --></div>
                        <button type="submit" class="main-btn">
                            Carte de mise à jour
                        </button>
                    </form>
                    <div id="card-errors" role="alert"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Scrips -->
    @section('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
     <script>
         toastr.options = {
             closeButton: true,
             progressBar: true,
             positionClass: 'toast-top-right',
             showMethod: 'slideDown',
             timeOut: 60000
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
        var stripe = Stripe('{{ config('services.stripe.key') }}');
        var elements = stripe.elements();
        var cardElement = elements.create('card');
        cardElement.mount('#card-element');
    
        var form = document.getElementById('payment-form');
        form.addEventListener('submit', function(event) {
            event.preventDefault();
    
            stripe.createToken(cardElement).then(function(result) {
                if (result.error) {
                    // Show error in #card-errors
                    document.getElementById('card-errors').textContent = result.error.message;
                } else {
                    // Send the token to your server
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'stripeToken');
                    hiddenInput.setAttribute('value', result.token.id);
                    form.appendChild(hiddenInput);
                    form.submit();
                }
            });
        });
    </script>

    @endsection
</x-after-paywall-layout>