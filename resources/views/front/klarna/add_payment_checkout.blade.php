<x-after-paywall-layout title="Klarna Checkout">
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

        .container {
            max-width: 375px; 
            margin: 0 auto;
            padding: 0 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .klarna-processing {
            text-align: center;
            padding: 40px 20px;
        }

        .klarna-processing h2 {
            font-family: 'SFRounded';
            font-size: 24px;
            line-height: 29px;
            color: #17191C;
            margin-bottom: 16px;
        }

        .klarna-processing p {
            font-size: 16px;
            line-height: 20px;
            color: #6B7280;
            margin-bottom: 24px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FFB3C7;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    @endsection

    <div class="container">
        <div class="klarna-processing">
            <h2>Redirection vers Klarna en cours...</h2>
            <p>Veuillez patienter pendant que nous vous redirigeons vers Klarna pour autoriser votre mode de paiement.</p>
            <div class="spinner"></div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger mt-4">{{ session('error') }}</div>
        @endif
    </div>

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
        const stripe = Stripe("{{ config('services.stripe.key') }}");

        const clientSecret = "{{ $clientSecret }}";

        // Auto-initiate Klarna flow on page load
        window.onload = function () {
            stripe.confirmKlarnaPayment(clientSecret, {
                payment_method: {
                    billing_details: {
                        email: "{{ $user->email }}"
                    }
                },
                return_url: "{{ route('confirm.klarna.payment.method') }}"
            }).then(function(result) {
                if (result.error) {
                    alert('Erreur Klarna: ' + result.error.message);
                    console.error(result.error.message);
                    // Redirect back on error
                    window.location.href = "{{ route('add.klarna.payment', ['uid' => request()->uid, 'st' => request()->st]) }}";
                }
            });
        };
    </script>
    @endsection
</x-after-paywall-layout>
