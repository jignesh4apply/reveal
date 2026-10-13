<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>

<script src="{{ asset('paywall/js/fontawesome/all.min.js') }}"></script>

<script src="{{ asset('paywall/js/owl/owl.carousel.min.js') }}"></script>

<script src="{{ asset('paywall/js/script.js') }}"></script>

<script src="https://cdn.amplitude.com/script/58b25352c5f050c3039f226c757dc9ba.js"></script>
<script>
    window.amplitude.init('58b25352c5f050c3039f226c757dc9ba', {
            "fetchRemoteConfig": true,
            "autocapture": true
        }, function() {
            // This callback ensures amplitude is initialized before calling logEvent
            amplitude.getInstance().logEvent("Paywall Viewed");
        });
</script>
@yield('scripts')
