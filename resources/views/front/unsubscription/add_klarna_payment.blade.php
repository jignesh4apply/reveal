<x-after-paywall-layout title="Ajouter Klarna">
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

        .main-section.full-height {
            min-height: 100vh;
        }

        .question-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .question-title .title {
            color: #17191C;
            font-size: 24px;
            line-height: 28px;
            margin: 0;
        }

        .question-desc {
            text-align: center;
            margin-bottom: 30px;
        }

        .question-desc p {
            color: #6B7280;
            font-size: 14px;
            line-height: 20px;
        }

        .plan-info {
            background-color: #F9FAFB;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .plan-info p {
            font-size: 14px;
            line-height: 20px;
            color: #374151;
        }

        .plan-info .plan-name {
            font-weight: 600;
            color: #7662F1;
        }

        .klarna-info {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 24px 16px;
            box-shadow: 0px 12px 24px -8px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .klarna-logo-wrapper {
            text-align: center;
            margin-bottom: 24px;
        }

        .klarna-logo {
            max-width: 120px;
            height: auto;
        }

        .klarna-features {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .klarna-features li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            line-height: 20px;
            color: #374151;
        }

        .klarna-features li:last-child {
            margin-bottom: 0;
        }

        .klarna-features .icon {
            width: 20px;
            height: 20px;
            background-color: #FFB3C7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
        }

        .main-btn {
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
            background-color: #FFB3C7 !important;
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
            box-shadow: 0px 12px 24px 0px rgba(255, 179, 199, 0.5);
        }

        .back-link {
            text-align: center;
            margin-top: 24px;
        }

        .back-link a {
            color: #7662F1;
            font-size: 14px;
            line-height: 20px;
            text-decoration: underline;
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
                    <h3 class="title">Configurer le paiement Klarna</h3>
                  </div>

                  <div class="question-desc">
                    <p>Pour continuer avec votre nouveau plan, veuillez configurer Klarna comme mode de paiement.</p>
                    <p style="margin-top: 12px; color: #7662F1; font-size: 13px;"><strong>Note:</strong> Des frais d'autorisation de 0,50€ seront facturés et.</p>
                  </div>

                  @if($plan)
                  <div class="plan-info">
                    <p>Modification vers : <span class="plan-name">{{ $plan->name }}</span></p>
                  </div>
                  @endif

                  <div class="klarna-info">
                    <div class="klarna-logo-wrapper">
                        <svg class="klarna-logo" viewBox="0 0 120 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect width="120" height="40" rx="4" fill="#FFB3C7"/>
                            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="18" font-weight="bold" font-family="Arial">Klarna</text>
                        </svg>
                    </div>

                    <ul class="klarna-features">
                        <li>
                            <span class="icon">✓</span>
                            <span>Paiements sécurisés et flexibles</span>
                        </li>
                        <li>
                            <span class="icon">✓</span>
                            <span>Gérez vos paiements facilement</span>
                        </li>
                        <li>
                            <span class="icon">✓</span>
                            <span>Protection des acheteurs incluse</span>
                        </li>
                    </ul>
                  </div>

                  <form action="{{ route('process.klarna.payment') }}" method="POST">
                    @csrf
                    <button type="submit" class="main-btn">
                        Continuer avec Klarna
                    </button>
                  </form>

                  <div class="back-link">
                    <a href="/subscription-details?uid={{ $uid }}&st={{ $st }}">Retour aux détails de l'abonnement</a>
                  </div>
            </div>
        </div>
    </section>

    <!-- Scrips -->
    @section('scripts')
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

    @endsection
</x-after-paywall-layout>
