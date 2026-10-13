<x-after-paywall-layout title="Confirm Payment">
    @section('styles')
    <link rel="stylesheet" href="font/sf-pro/stylesheet.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    @endsection
    <section class="main-section">
       <div class="container">
          <div class="inner-section">
             <div class="main-header">
                 <div class="inner-header age-header">
                     <div class="header-top">
                         <div>
                             <img class="logo" src="{{ asset('webAssets/images/svgs/logo.svg') }}">
                         </div>
                     </div>
                 </div>
             </div>
            <div class="question-title">
                <h3 class="title">Complétez votre paiement</h3>
            </div>
            <div class="download-box">
                <h3 class="download-box-title">
                    Nous devons authentifier votre paiement. Veuillez suivre les instructions ci-dessous.
                </h3>
            </div>
            <button id="confirmPayment" class="form-submit">Continuer</button>
        </div>
    </div>
 </section>
 @section('scripts')
<script src="https://js.stripe.com/v3/"></script>
    <?php 
        $u =  base64_encode("user_id=".$user_id."&plan_id=".$plan_id."&invoiceID=".$invoiceID)
    ?>
    <script>
        const stripe = Stripe("{{ config('services.stripe.key') }}");
        const clientSecret = "{{ $clientSecret }}";
        const u = "{{ $u }}";

        document.getElementById('confirmPayment').addEventListener('click', () => {
            stripe.confirmCardPayment(clientSecret).then((result) => {
                if (result.error) {
                    alert("Payment failed: " + result.error.message);
                    window.location.href = "/packages";
                } else if (result.paymentIntent.status === "succeeded") {
                    window.location.href = "/newSubscription-3dPayment-success?u="+u; // Redirect to a success page
                } else {
                    alert("Payment requires further action.");
                }
            });
        });
    </script>
   @endsection
</x-after-paywall-layout>
