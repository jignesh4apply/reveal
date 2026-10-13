<x-paywall-layout title="Klarna Checkout">
    @section('styles')
    @endsection

    <div class="container mt-5">
        <h2 class="mb-4">Redirection vers Klarna en cours...</h2>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
    </div>

    @section('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe("{{ config('services.stripe.key') }}");

        const clientSecret = "{{ $clientSecret }}";

        // Optional: pre-fill info for better Klarna experience
        // const billingDetails = {
        //     name: "{{ $user->name ?? '' }}",
        //     email: "{{ $user->email }}",
        //     phone: "{{ $user->phone ?? '' }}",
        //     address: {
        //         line1: "{{ $user->address ?? '12 rue Exemple' }}",
        //         postal_code: "{{ $user->postal_code ?? '75001' }}",
        //         city: "{{ $user->city ?? 'Paris' }}",
        //         country: 'FR'
        //     }
        // };

        // Auto-initiate Klarna flow on page load
        window.onload = function () {
            stripe.confirmKlarnaPayment(clientSecret, {
                payment_method: {
                    // billing_details: billingDetails
                },
                return_url: "{{ route('klarna.confirmation') }}"
            }).then(function(result) {
                if (result.error) {
                    alert('Erreur Klarna: ' + result.error.message);
                    console.error(result.error.message);
                }
            });
        };
    </script>
    @endsection
</x-paywall-layout>
