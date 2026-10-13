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
        $u =  base64_encode("user_id=".$user_id."&plan_id=".$plan_id."&invoiceID=".$invoiceID);
    ?>
    <script>
        const stripe = Stripe("{{ config('services.stripe.key') }}");
        const clientSecret = "{{ $clientSecret }}";
        const u = "{{ $u }}";

        const confirmBtn = document.getElementById('confirmPayment');
        let paymentProcessing = false; // Prevent double-clicks

        confirmBtn.addEventListener('click', async () => {
            if (paymentProcessing) return; // Already running
            paymentProcessing = true;

            confirmBtn.disabled = true;
            confirmBtn.innerText = "Traitement en cours...";

            try {
                const result = await stripe.confirmCardPayment(clientSecret);

                if (result.error) {
                    alert("Le paiement a échoué : " + result.error.message);
                    await fetch('/delete-user-after-failed-attempt', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        body: JSON.stringify({ userId: "{{ $user_id }}" })
                    });
                    window.location.href = "/get-package";
                } 
                else if (result.paymentIntent && result.paymentIntent.status === "succeeded") {
                    // Prevent any accidental double redirects
                    if (!window.hasRedirectedToSuccess) {
                        window.hasRedirectedToSuccess = true;
                        window.location.replace("/3dPayment-success?u=" + encodeURIComponent(u));
                    }
                } 
                else {
                    alert("Le paiement nécessite une action supplémentaire.");
                }
            } catch (error) {
                console.error("Unexpected error:", error);
                alert("Une erreur est survenue. Veuillez réessayer.");
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.innerText = "Continuer";
                paymentProcessing = false;
            }
        });
    </script>
    @endsection

</x-after-paywall-layout>
